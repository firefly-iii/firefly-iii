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
use Log;

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
            Log::debug('handle_debts is disabled.');

            return;
        }
        if (null !== $this->group && null === $this->account) {
            Log::debug('Have to handle a group.');
            $this->processGroup();
        }
        if (null !== $this->account && null === $this->group) {
            Log::debug('Have to handle an account.');
            // work based on account.
            $this->processAccount();
        }
        if (0 === count($this->work)) {
            Log::debug('No work accounts, do not do CreditRecalculationService');

            return;
        }
        Log::debug('Will now do CreditRecalculationService');
        $this->processWork();
    }

    /**
     *
     */
    private function processGroup(): void
    {
        /** @var TransactionJournal $journal */
        foreach ($this->group->transactionJournals as $journal) {
            if (0 === count($this->work)) {
                try {
                    $this->findByJournal($journal);
                } catch (FireflyException $e) {
                    Log::error($e->getTraceAsString());
                    Log::error(sprintf('Could not find work account for transaction group #%d.', $this->group->id));
                }
            }
        }
        Log::debug(sprintf('Done with %s', __METHOD__));
    }

    /**
     * @param  TransactionJournal  $journal
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
            Log::debug(sprintf('Dest account type is "%s", include it.', $destination->accountType->type));
            $this->work[] = $destination;
        }
        if (in_array($source->accountType->type, $valid, true)) {
            Log::debug(sprintf('Src account type is "%s", include it.', $source->accountType->type));
            $this->work[] = $source;
        }
    }

    /**
     * @param  TransactionJournal  $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        return $this->getAccountByDirection($journal, '<');
    }

    /**
     * @param  TransactionJournal  $journal
     * @param  string  $direction
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
     * @param  TransactionJournal  $journal
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
            Log::debug(sprintf('Account type is "%s", include it.', $this->account->accountType->type));
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
        Log::debug(sprintf('Done with %s', __METHOD__));
    }

    /**
     * @param  Account  $account
     */
    private function processWorkAccount(Account $account): void
    {
        Log::debug(sprintf('Now in %s(#%d)', __METHOD__, $account->id));

        // get opening balance (if present)
        $this->repository->setUser($account->user);
        $startOfDebt = $this->repository->getOpeningBalanceAmount($account) ?? '0';
        $leftOfDebt  = app('steam')->positive($startOfDebt);

        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);

        // amount is positive or negative, doesn't matter.
        $factory->crud($account, 'start_of_debt', $startOfDebt);

        // get direction of liability:
        $direction = (string)$this->repository->getMetaValue($account, 'liability_direction');

        // now loop all transactions (except opening balance and credit thing)
        $transactions = $account->transactions()->get();
        Log::debug(sprintf('Going to process %d transaction(s)', $transactions->count()));
        Log::debug(sprintf('Account currency is #%d (%s)', $account->id, $this->repository->getAccountCurrency($account)?->code));
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $leftOfDebt = $this->processTransaction($account, $direction, $transaction, $leftOfDebt);
        }
        $factory->crud($account, 'current_debt', $leftOfDebt);

        Log::debug(sprintf('Done with %s(#%d)', __METHOD__, $account->id));
    }

    /**
     * @param  Account  $account
     * @param  string  $direction
     * @param  Transaction  $transaction
     * @param  string  $amount
     *
     * @return string
     */
    private function processTransaction(Account $account, string $direction, Transaction $transaction, string $leftOfDebt): string
    {
        Log::debug(sprintf('Now in processTransaction(#%d, %s)', $transaction->id, $leftOfDebt));
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
            Log::debug('Since direction is "", do nothing.');

            return $leftOfDebt;
        }
        if (TransactionType::LIABILITY_CREDIT === $type || TransactionType::OPENING_BALANCE === $type) {
            Log::debug(sprintf('Skip group #%d, journal #%d of type "%s"', $journal->id, $groupId, $type));
            return $leftOfDebt;
        }

        // amount to use depends on the currency:
        $usedAmount = $transaction->amount;
        if (null !== $foreignCurrency && $foreignCurrency->id === $accountCurrency->id) {
            $usedAmount = $transaction->foreign_amount;
            Log::debug('Will use foreign amount to match account currency.');
        }

        Log::debug(sprintf('Processing group #%d, journal #%d of type "%s"', $journal->id, $groupId, $type));

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
            $newLeftOfDebt = bcadd($leftOfDebt, app('steam')->positive($usedAmount));
            Log::debug(sprintf('[1] Is withdrawal (%s) into liability, left of debt = %s.', $usedAmount, $newLeftOfDebt));
            return $newLeftOfDebt;
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
            $newLeftOfDebt = bcsub($leftOfDebt, app('steam')->positive($usedAmount));
            Log::debug(
                sprintf(
                    '[2] Is withdrawal (%s) away from liability, left of debt = %s.',
                    $usedAmount,
                    $newLeftOfDebt
                )
            );
            return $newLeftOfDebt;
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
            $newLeftOfDebt = bcsub($leftOfDebt, app('steam')->positive($usedAmount));
            Log::debug(sprintf('[3] Is deposit (%s) away from liability, left of debt = %s.', $usedAmount, $newLeftOfDebt));
            return $newLeftOfDebt;
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
            $newLeftOfDebt = bcadd($leftOfDebt, app('steam')->positive($usedAmount));
            Log::debug(sprintf('[4] Is deposit (%s) into liability, left of debt = %s.', $usedAmount, $newLeftOfDebt));
            return $newLeftOfDebt;
        }

        // in any other case, remove amount from left of debt.
        if (in_array($type, [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER], true)) {
            $newLeftOfDebt = bcadd($leftOfDebt, bcmul($usedAmount, '-1'));
            Log::debug(
                sprintf('[5] Fallback: transaction is withdrawal/deposit/transfer, remove amount %s from left of debt, = %s.', $usedAmount, $newLeftOfDebt)
            );
            return $newLeftOfDebt;
        }

        Log::warning(sprintf('[6] Catch-all, should not happen. Left of debt = %s', $leftOfDebt));

        return $leftOfDebt;
    }

    /**
     * @param  Account|null  $account
     */
    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @param  TransactionGroup  $group
     */
    public function setGroup(TransactionGroup $group): void
    {
        $this->group = $group;
    }
}
