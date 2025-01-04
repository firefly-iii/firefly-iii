<?php

/*
 * CreditRecalculateService.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Services\Internal\Support;

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Class CreditRecalculateService
 */
class CreditRecalculateService
{
    private ?Account                   $account;
    private ?TransactionGroup          $group;
    private AccountRepositoryInterface $repository;
    private array                      $work;

    /**
     * CreditRecalculateService constructor.
     */
    public function __construct()
    {
        $this->group   = null;
        $this->account = null;
        $this->work    = [];
    }

    public function recalculate(): void
    {
        if (true !== config('firefly.feature_flags.handle_debts')) {
            return;
        }
        if (null !== $this->group && null === $this->account) {
            $this->processGroup();
        }
        if (null !== $this->account && null === $this->group) {
            // work based on account.
            $this->processAccount();
        }
        if (0 === count($this->work)) {
            return;
        }
        $this->processWork();
    }

    private function processGroup(): void
    {
        /** @var TransactionJournal $journal */
        foreach ($this->group->transactionJournals as $journal) {
            try {
                $this->findByJournal($journal);
            } catch (FireflyException $e) {
                Log::error($e->getTraceAsString());
                Log::error(sprintf('Could not find work account for transaction group #%d.', $this->group->id));
            }
        }
    }

    /**
     * @throws FireflyException
     */
    private function findByJournal(TransactionJournal $journal): void
    {
        $source      = $this->getSourceAccount($journal);
        $destination = $this->getDestinationAccount($journal);

        // destination or source must be liability.
        $valid       = config('firefly.valid_liabilities');
        if (in_array($destination->accountType->type, $valid, true)) {
            $this->work[] = $destination;
        }
        if (in_array($source->accountType->type, $valid, true)) {
            $this->work[] = $source;
        }
    }

    /**
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        return $this->getAccountByDirection($journal, '<');
    }

    /**
     * @throws FireflyException
     */
    private function getAccountByDirection(TransactionJournal $journal, string $direction): Account
    {
        /** @var null|Transaction $transaction */
        $transaction  = $journal->transactions()->where('amount', $direction, '0')->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Cannot find "%s"-transaction of journal #%d', $direction, $journal->id));
        }

        /** @var null|Account $foundAccount */
        $foundAccount = $transaction->account;
        if (null === $foundAccount) {
            throw new FireflyException(sprintf('Cannot find "%s"-account of transaction #%d of journal #%d', $direction, $transaction->id, $journal->id));
        }

        return $foundAccount;
    }

    /**
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        return $this->getAccountByDirection($journal, '>');
    }

    private function processAccount(): void
    {
        $valid = config('firefly.valid_liabilities');
        if (in_array($this->account->accountType->type, $valid, true)) {
            $this->work[] = $this->account;
        }
    }

    private function processWork(): void
    {
        $this->repository = app(AccountRepositoryInterface::class);
        foreach ($this->work as $account) {
            $this->processWorkAccount($account);
        }
    }

    private function processWorkAccount(Account $account): void
    {
        Log::debug(sprintf('Now processing account #%d ("%s").', $account->id, $account->name));
        // get opening balance (if present)
        $this->repository->setUser($account->user);
        $direction      = (string) $this->repository->getMetaValue($account, 'liability_direction');
        $openingBalance = $this->repository->getOpeningBalance($account);
        if (null !== $openingBalance) {
            //            Log::debug(sprintf('Found opening balance transaction journal #%d', $openingBalance->id));
            // if account direction is "debit" ("I owe this amount") the opening balance must always be AWAY from the account:
            if ('debit' === $direction) {
                $this->validateOpeningBalance($account, $openingBalance);
            }
        }
        $startOfDebt    = $this->repository->getOpeningBalanceAmount($account) ?? '0';
        $leftOfDebt     = app('steam')->positive($startOfDebt);
        //        Log::debug(sprintf('Start of debt is "%s", so initial left of debt is "%s"', app('steam')->bcround($startOfDebt, 2), app('steam')->bcround($leftOfDebt, 2)));

        /** @var AccountMetaFactory $factory */
        $factory        = app(AccountMetaFactory::class);

        // amount is positive or negative, doesn't matter.
        $factory->crud($account, 'start_of_debt', $startOfDebt);

        //        Log::debug(sprintf('Debt direction is "%s"', $direction));

        // now loop all transactions (except opening balance and credit thing)
        $transactions   = $account->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->orderBy('transaction_journals.date', 'ASC')
            ->get(['transactions.*'])
        ;
        $total          = $transactions->count();
        //        Log::debug(sprintf('Found %d transaction(s) to process.', $total));

        /** @var Transaction $transaction */
        foreach ($transactions as $index => $transaction) {
            //            Log::debug(sprintf('[%d/%d] Processing transaction.', $index + 1, $total));
            $leftOfDebt = $this->processTransaction($account, $direction, $transaction, $leftOfDebt);
        }
        $factory->crud($account, 'current_debt', $leftOfDebt);
        Log::debug(sprintf('Done processing account #%d ("%s")', $account->id, $account->name));
    }

    /**
     * If account direction is "debit" ("I owe this amount") the opening balance must always be AWAY from the account:
     */
    private function validateOpeningBalance(Account $account, TransactionJournal $openingBalance): void
    {
        /** @var Transaction $source */
        $source = $openingBalance->transactions()->where('amount', '<', 0)->first();

        /** @var Transaction $dest */
        $dest   = $openingBalance->transactions()->where('amount', '>', 0)->first();
        if ($source->account_id !== $account->id) {
            Log::info(sprintf('Liability #%d has a reversed opening balance. Will fix this now.', $account->id));
            Log::debug(sprintf('Source amount "%s" is now "%s"', $source->amount, app('steam')->positive($source->amount)));
            Log::debug(sprintf('Destination amount "%s" is now "%s"', $dest->amount, app('steam')->negative($dest->amount)));
            $source->amount = app('steam')->positive($source->amount);
            $dest->amount   = app('steam')->negative($source->amount);
            if (null !== $source->foreign_amount && '' !== $source->foreign_amount) {
                $source->foreign_amount = app('steam')->positive($source->foreign_amount);
                Log::debug(sprintf('Source foreign amount "%s" is now "%s"', $source->foreign_amount, app('steam')->positive($source->foreign_amount)));
            }
            if (null !== $dest->foreign_amount && '' !== $dest->foreign_amount) {
                $dest->foreign_amount = app('steam')->negative($dest->foreign_amount);
                Log::debug(sprintf('Destination amount "%s" is now "%s"', $dest->foreign_amount, app('steam')->negative($dest->foreign_amount)));
            }
            $source->save();
            $dest->save();

            return;
        }
        // Log::debug('Opening balance is valid');
    }

    /**
     * A complex and long method, but rarely used luckily.
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function processTransaction(Account $account, string $direction, Transaction $transaction, string $leftOfDebt): string
    {
        $journal         = $transaction->transactionJournal;

        // here be null pointers.
        if (null === $journal) {
            Log::warning(sprintf('Transaction #%d has no journal.', $transaction->id));

            return $leftOfDebt;
        }


        $foreignCurrency = $transaction->foreignCurrency;
        $accountCurrency = $this->repository->getAccountCurrency($account);
        $type            = $journal->transactionType->type;
        //        Log::debug(sprintf('Left of debt is: %s', app('steam')->bcround($leftOfDebt, 2)));

        if ('' === $direction) {
            //            Log::warning('Direction is empty, so do nothing.');

            return $leftOfDebt;
        }
        if (TransactionTypeEnum::LIABILITY_CREDIT->value === $type || TransactionTypeEnum::OPENING_BALANCE->value === $type) {
            // Log::warning(sprintf('Transaction type is "%s", so do nothing.', $type));

            return $leftOfDebt;
        }
        //        Log::debug(sprintf('Liability direction is "%s"', $direction));

        // amount to use depends on the currency:
        $usedAmount      = $this->getAmountToUse($transaction, $accountCurrency, $foreignCurrency);
        $isSameAccount   = $account->id === $transaction->account_id;
        $isDebit         = 'debit' === $direction;
        $isCredit        = 'credit' === $direction;

        if ($isSameAccount && $isCredit && $this->isWithdrawalIn($usedAmount, $type)) { // case 1
            $usedAmount = app('steam')->positive($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 1 (withdrawal into credit liability): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }

        if ($isSameAccount && $isCredit && $this->isWithdrawalOut($usedAmount, $type)) { // case 2
            $usedAmount = app('steam')->positive($usedAmount);

            return bcsub($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 2 (withdrawal away from liability): %s - %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }

        if ($isSameAccount && $isCredit && $this->isDepositOut($usedAmount, $type)) { // case 3
            $usedAmount = app('steam')->positive($usedAmount);

            return bcsub($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 3 (deposit away from liability): %s - %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }

        if ($isSameAccount && $isCredit && $this->isDepositIn($usedAmount, $type)) { // case 4
            $usedAmount = app('steam')->positive($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 4 (deposit into credit liability): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }
        if ($isSameAccount && $isCredit && $this->isTransferIn($usedAmount, $type)) { // case 5
            $usedAmount = app('steam')->positive($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 5 (transfer into credit liability): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }
        if ($isSameAccount && $isDebit && $this->isWithdrawalIn($usedAmount, $type)) { // case 6
            $usedAmount = app('steam')->positive($usedAmount);

            return bcsub($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 6 (withdrawal into debit liability): %s - %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }
        if ($isSameAccount && $isDebit && $this->isDepositOut($usedAmount, $type)) { // case 7
            $usedAmount = app('steam')->positive($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 7 (deposit away from liability): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }
        if ($isSameAccount && $isDebit && $this->isWithdrawalOut($usedAmount, $type)) { // case 8
            $usedAmount = app('steam')->positive($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case 8 (withdrawal away from liability): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }

        if ($isSameAccount && $isDebit && $this->isTransferIn($usedAmount, $type)) { // case 9
            $usedAmount = app('steam')->positive($usedAmount);

            return bcsub($leftOfDebt, $usedAmount);
            // 2024-10-05, #9225 this used to say you would owe more, but a transfer INTO a debit from wherever means you owe LESS.
            //            Log::debug(sprintf('Case 9 (transfer into debit liability, means you owe LESS): %s - %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }
        if ($isSameAccount && $isDebit && $this->isTransferOut($usedAmount, $type)) { // case 10
            $usedAmount = app('steam')->positive($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            // 2024-10-05, #9225 this used to say you would owe less, but a transfer OUT OF a debit from wherever means you owe MORE.
            //            Log::debug(sprintf('Case 10 (transfer out of debit liability, means you owe MORE): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }

        // in any other case, remove amount from left of debt.
        if (in_array($type, [TransactionTypeEnum::WITHDRAWAL->value, TransactionTypeEnum::DEPOSIT->value, TransactionTypeEnum::TRANSFER->value], true)) {
            $usedAmount = app('steam')->negative($usedAmount);

            return bcadd($leftOfDebt, $usedAmount);
            //            Log::debug(sprintf('Case X (all other cases): %s + %s = %s', app('steam')->bcround($leftOfDebt, 2), app('steam')->bcround($usedAmount, 2), app('steam')->bcround($result, 2)));
        }

        Log::warning(sprintf('[-1] Catch-all, should not happen. Left of debt = %s', app('steam')->bcround($leftOfDebt, 2)));

        return $leftOfDebt;
    }

    private function getAmountToUse(Transaction $transaction, TransactionCurrency $accountCurrency, ?TransactionCurrency $foreignCurrency): string
    {
        $usedAmount = $transaction->amount;
        //        Log::debug(sprintf('Amount of transaction is %s', app('steam')->bcround($usedAmount, 2)));
        if (null !== $foreignCurrency && $foreignCurrency->id === $accountCurrency->id) {
            $usedAmount = $transaction->foreign_amount;
            //            Log::debug(sprintf('Overruled by foreign amount. Amount of transaction is now %s', app('steam')->bcround($usedAmount, 2)));
        }

        return $usedAmount;
    }

    /**
     * It's a withdrawal into this liability (from asset).
     *
     * Case 1 = credit
     * if it's a credit ("I am owed"), this increases the amount due,
     * because we're lending person X more money
     *
     * Case 6 = debit
     * if it's a debit ("I owe this amount"), this decreases the amount due,
     * because we're paying off the debt
     */
    private function isWithdrawalIn(string $amount, string $transactionType): bool
    {
        return TransactionTypeEnum::WITHDRAWAL->value === $transactionType && 1 === bccomp($amount, '0');
    }

    /**
     * it's a withdrawal away from this liability (into expense account).
     *
     * Case 2
     * if it's a credit ("I am owed"), this decreases the amount due,
     * because we're sending money away from the loan (like loan forgiveness)
     *
     * Case 8
     * if it's a debit ("I owe this amount") this increase the amount due.
     * because we are paying interest.
     */
    private function isWithdrawalOut(string $amount, string $transactionType): bool
    {
        return TransactionTypeEnum::WITHDRAWAL->value === $transactionType && -1 === bccomp($amount, '0');
    }

    /**
     * it's a deposit out of this liability (to asset).
     *
     * case 3
     * if it's a credit ("I am owed") this decreases the amount due.
     * because the person is paying us back.
     *
     * case 7
     * if it's a debit ("I owe") this increases the amount due.
     * because we are borrowing more money.
     */
    private function isDepositOut(string $amount, string $transactionType): bool
    {
        return TransactionTypeEnum::DEPOSIT->value === $transactionType && -1 === bccomp($amount, '0');
    }

    /**
     * case 4
     * it's a deposit into this liability (from revenue account).
     * if it's a credit ("I am owed") this increases the amount due.
     * because the person is having to pay more money.
     */
    private function isDepositIn(string $amount, string $transactionType): bool
    {
        return TransactionTypeEnum::DEPOSIT->value === $transactionType && 1 === bccomp($amount, '0');
    }

    /**
     * case 5: transfer into loan (from other loan).
     * if it's a credit ("I am owed") this increases the amount due,
     * because the person has to pay more back.
     *
     * case 8: transfer into loan (from other loan).
     * if it's a debit ("I owe") this decreases the amount due.
     * because the person has to pay more back.
     */
    private function isTransferIn(string $amount, string $transactionType): bool
    {
        return TransactionTypeEnum::TRANSFER->value === $transactionType && 1 === bccomp($amount, '0');
    }

    /**
     * it's a transfer out of loan (from other loan)
     *
     * case 9
     * if it's a debit ("I owe") this decreases the amount due.
     * because we remove money from the amount left to owe
     */
    private function isTransferOut(string $amount, string $transactionType): bool
    {
        return TransactionTypeEnum::TRANSFER->value === $transactionType && -1 === bccomp($amount, '0');
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    public function setGroup(TransactionGroup $group): void
    {
        $this->group = $group;
    }
}
