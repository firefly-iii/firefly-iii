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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
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

    /**
     *
     */
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

    /**
     *
     */
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
     * @param TransactionJournal $journal
     *
     * @throws FireflyException
     */
    private function findByJournal(TransactionJournal $journal): void
    {
        $source      = $this->getSourceAccount($journal);
        $destination = $this->getDestinationAccount($journal);

        // destination or source must be liability.
        $valid = config('firefly.valid_liabilities');
        if (in_array($destination->accountType->type, $valid, true)) {
            $this->work[] = $destination;
        }
        if (in_array($source->accountType->type, $valid, true)) {
            $this->work[] = $source;
        }
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        return $this->getAccountByDirection($journal, '<');
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $direction
     *
     * @return Account
     * @throws FireflyException
     */
    private function getAccountByDirection(TransactionJournal $journal, string $direction): Account
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->where('amount', $direction, '0')->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Cannot find "%s"-transaction of journal #%d', $direction, $journal->id));
        }
        $foundAccount = $transaction->account;
        if (null === $foundAccount) {
            throw new FireflyException(sprintf('Cannot find "%s"-account of transaction #%d of journal #%d', $direction, $transaction->id, $journal->id));
        }

        return $foundAccount;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        return $this->getAccountByDirection($journal, '>');
    }

    /**
     *
     */
    private function processAccount(): void
    {
        $valid = config('firefly.valid_liabilities');
        if (in_array($this->account->accountType->type, $valid, true)) {
            $this->work[] = $this->account;
        }
    }

    /**
     *
     */
    private function processWork(): void
    {
        $this->repository = app(AccountRepositoryInterface::class);
        foreach ($this->work as $account) {
            $this->processWorkAccount($account);
        }
    }

    /**
     * @param Account $account
     */
    private function processWorkAccount(Account $account): void
    {
        app('log')->debug(sprintf('Now processing account #%d ("%s")', $account->id, $account->name));
        // get opening balance (if present)
        $this->repository->setUser($account->user);
        $startOfDebt = $this->repository->getOpeningBalanceAmount($account) ?? '0';
        $leftOfDebt  = app('steam')->positive($startOfDebt);

        app('log')->debug(sprintf('Start of debt is "%s", so initial left of debt is "%s"', $startOfDebt, $leftOfDebt));

        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);

        // amount is positive or negative, doesn't matter.
        $factory->crud($account, 'start_of_debt', $startOfDebt);

        // get direction of liability:
        $direction = (string)$this->repository->getMetaValue($account, 'liability_direction');

        app('log')->debug(sprintf('Debt direction is "%s"', $direction));

        // now loop all transactions (except opening balance and credit thing)
        $transactions = $account->transactions()
                                ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                ->orderBy('transaction_journals.date', 'ASC')
                                ->get(['transactions.*']);
        $total        = $transactions->count();
        app('log')->debug(sprintf('Found %d transaction(s) to process.', $total));
        /** @var Transaction $transaction */
        foreach ($transactions as $index => $transaction) {
            app('log')->debug(sprintf('[%d/%d] Processing transaction.', $index + 1, $total));
            $leftOfDebt = $this->processTransaction($account, $direction, $transaction, $leftOfDebt);
        }
        $factory->crud($account, 'current_debt', $leftOfDebt);
        app('log')->debug(sprintf('Done processing account #%d ("%s")', $account->id, $account->name));
    }

    /**
     * @param Account     $account
     * @param string      $direction
     * @param Transaction $transaction
     * @param string      $leftOfDebt
     *
     * @return string
     */
    private function processTransaction(Account $account, string $direction, Transaction $transaction, string $leftOfDebt): string
    {
        app('log')->debug(sprintf('Left of debt is: %s', $leftOfDebt));
        $journal         = $transaction->transactionJournal;
        $foreignCurrency = $transaction->foreignCurrency;
        $accountCurrency = $this->repository->getAccountCurrency($account);
        $groupId         = $journal->transaction_group_id;
        $type            = $journal->transactionType->type;
        /** @var Transaction $destTransaction */
        $destTransaction = $journal->transactions()->where('amount', '>', '0')->first();
        /** @var Transaction $sourceTransaction */
        $sourceTransaction = $journal->transactions()->where('amount', '<', '0')->first();

        if ('' === $direction) {
            app('log')->warning('Direction is empty, so do nothing.');
            return $leftOfDebt;
        }
        if (TransactionType::LIABILITY_CREDIT === $type || TransactionType::OPENING_BALANCE === $type) {
            app('log')->warning(sprintf('Transaction type is "%s", so do nothing.', $type));
            return $leftOfDebt;
        }

        // amount to use depends on the currency:
        $usedAmount = $transaction->amount;
        app('log')->debug(sprintf('Amount of transaction is %s', $usedAmount));
        if (null !== $foreignCurrency && $foreignCurrency->id === $accountCurrency->id) {
            $usedAmount = $transaction->foreign_amount;
            app('log')->debug(sprintf('Overruled by foreign amount. Amount of transaction is now %s', $usedAmount));
        }

        // Case 1
        // it's a withdrawal into this liability (from asset).
        // if it's a credit ("I am owed"), this increases the amount due,
        // because we're lending person X more money
        if (
            $type === TransactionType::WITHDRAWAL
            && (int)$account->id === (int)$transaction->account_id
            && 1 === bccomp($usedAmount, '0')
            && 'credit' === $direction
        ) {
            $usedAmount = app('steam')->positive($usedAmount);
            $result     = bcadd($leftOfDebt, $usedAmount);
            app('log')->debug(sprintf('Case 1 (withdrawal into credit liability): %s + %s = %s', $leftOfDebt, $usedAmount, $result));
            return $result;
        }

        // Case 2
        // it's a withdrawal away from this liability (into expense account).
        // if it's a credit ("I am owed"), this decreases the amount due,
        // because we're sending money away from the loan (like loan forgiveness)
        if (
            $type === TransactionType::WITHDRAWAL
            && (int)$account->id === (int)$sourceTransaction->account_id
            && -1 === bccomp($usedAmount, '0')
            && 'credit' === $direction
        ) {
            $usedAmount = app('steam')->positive($usedAmount);
            $result     = bcsub($leftOfDebt, $usedAmount);
            app('log')->debug(sprintf('Case 2 (withdrawal away from liability): %s - %s = %s', $leftOfDebt, $usedAmount, $result));
            return $result;
        }

        // case 3
        // it's a deposit out of this liability (to asset).
        // if it's a credit ("I am owed") this decreases the amount due.
        // because the person is paying us back.
        if (
            $type === TransactionType::DEPOSIT
            && (int)$account->id === (int)$transaction->account_id
            && -1 === bccomp($usedAmount, '0')
            && 'credit' === $direction
        ) {
            $usedAmount = app('steam')->positive($usedAmount);
            $result     = bcsub($leftOfDebt, $usedAmount);
            app('log')->debug(sprintf('Case 3 (deposit away from liability): %s - %s = %s', $leftOfDebt, $usedAmount, $result));
            return $result;
        }

        // case 4
        // it's a deposit into this liability (from revenue account).
        // if it's a credit ("I am owed") this increases the amount due.
        // because the person is having to pay more money.
        if (
            $type === TransactionType::DEPOSIT
            && (int)$account->id === (int)$destTransaction->account_id
            && 1 === bccomp($usedAmount, '0')
            && 'credit' === $direction
        ) {
            $usedAmount = app('steam')->positive($usedAmount);
            $result     = bcadd($leftOfDebt, $usedAmount);
            app('log')->debug(sprintf('Case 4 (deposit into credit liability): %s + %s = %s', $leftOfDebt, $usedAmount, $result));
            return $result;
        }
        // case 5: transfer into loan (from other loan).
        // if it's a credit ("I am owed") this increases the amount due,
        // because the person has to pay more back.
        if (
            $type === TransactionType::TRANSFER
            && (int)$account->id === (int)$destTransaction->account_id
            && 1 === bccomp($usedAmount, '0')
            && 'credit' === $direction
        ) {
            $usedAmount = app('steam')->positive($usedAmount);
            $result     = bcadd($leftOfDebt, $usedAmount);
            app('log')->debug(sprintf('Case 5 (transfer into credit liability): %s + %s = %s', $leftOfDebt, $usedAmount, $result));
            return $result;
        }

        // in any other case, remove amount from left of debt.
        if (in_array($type, [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER], true)) {
            $usedAmount = app('steam')->negative($usedAmount);
            $result     = bcadd($leftOfDebt, $usedAmount);
            app('log')->debug(sprintf('Case X (all other cases): %s + %s = %s', $leftOfDebt, $usedAmount, $result));
            return $result;
        }

        Log::warning(sprintf('[6] Catch-all, should not happen. Left of debt = %s', $leftOfDebt));

        return $leftOfDebt;
    }

    /**
     * @param Account|null $account
     */
    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @param TransactionGroup $group
     */
    public function setGroup(TransactionGroup $group): void
    {
        $this->group = $group;
    }
}
