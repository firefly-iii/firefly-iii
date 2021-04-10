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

namespace FireflyIII\Services\Internal\Support;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Log;

class CreditRecalculateService
{
    private TransactionGroup $group;

    /**
     *
     */
    public function recalculate(): void
    {
        if (true !== config('firefly.feature_flags.handle_debts')) {
            Log::debug('handle_debts is disabled.');

            return;
        }
        Log::error('TODO');

        return;
        Log::debug(sprintf('Now in %s', __METHOD__));
        /** @var TransactionJournal $journal */
        foreach ($this->group->transactionJournals as $journal) {
            try {
                $this->recalculateJournal($journal);
            } catch (FireflyException $e) {
                Log::error($e->getTraceAsString());
                Log::error('Could not recalculate');
            }
        }
        Log::debug(sprintf('Done with %s', __METHOD__));;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @throws FireflyException
     */
    private function recalculateJournal(TransactionJournal $journal): void
    {
        if (TransactionType::DEPOSIT !== $journal->transactionType->type) {
            Log::debug('Journal is not a deposit.');

            return;
        }
        $source      = $this->getSourceAccount($journal);
        $destination = $this->getDestinationAccount($journal);
        // destination must be liability, source must be expense.
        if (AccountType::REVENUE !== $source->accountType->type) {
            Log::debug('Source is not a revenue account.');

            return;
        }
        if (!in_array($destination->accountType->type, config('firefly.valid_liabilities'))) {
            Log::debug('Destination is not a liability.');

            return;
        }
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($destination->user);
        $direction = $repository->getMetaValue($destination, 'liability_direction');
        if ('credit' !== $direction) {
            Log::debug(sprintf('Destination liabiltiy direction is "%s", do nothing.', $direction));
        }
        /*
         * This destination is a liability and an incoming debt. The amount paid into the liability changes the original debt amount.
         *
         */
        Log::debug('Do something!');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal): Account
    {
        return $this->getAccount($journal, '<');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Account
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal): Account
    {
        return $this->getAccount($journal, '>');
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $direction
     *
     * @return Account
     * @throws FireflyException
     */
    private function getAccount(TransactionJournal $journal, string $direction): Account
    {
        /** @var Transaction $transaction */
        $transaction = $journal->transactions()->where('amount', $direction, '0')->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Cannot find "%s"-transaction of journal #%d', $direction, $journal->id));
        }
        $account = $transaction->account;
        if (null === $account) {
            throw new FireflyException(sprintf('Cannot find "%s"-account of transaction #%d of journal #%d', $direction, $transaction->id, $journal->id));
        }

        return $account;
    }

    /**
     * @param TransactionGroup $group
     */
    public function setGroup(TransactionGroup $group): void
    {
        $this->group = $group;
    }


}