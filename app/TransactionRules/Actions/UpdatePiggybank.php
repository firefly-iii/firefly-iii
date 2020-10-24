<?php

/**
 * UpdatePiggybank.php
 * Copyright (c) 2020 james@firefly-iii.org
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


namespace FireflyIII\TransactionRules\Actions;


use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class UpdatePiggybank
 */
class UpdatePiggybank implements ActionInterface
{

    /** @var RuleAction The rule action */
    private $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * @param array     $journalArray
     * @param PiggyBank $piggyBank
     * @param string    $amount
     */
    private function addAmount(array $journalArray, PiggyBank $piggyBank, string $amount): void
    {
        $user = User::find($journalArray['user_id']);
        $journal = $user->transactionJournals()->find($journalArray['transaction_journal_id']);
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($journal->user);

        // how much can we add to the piggy bank?
        $toAdd = bcsub($piggyBank->targetamount, $repository->getCurrentAmount($piggyBank));
        Log::debug(sprintf('Max amount to add to piggy bank is %s, amount is %s', $toAdd, $amount));

        // update amount to fit:
        $amount = -1 === bccomp($amount, $toAdd) ? $amount : $toAdd;
        Log::debug(sprintf('Amount is now %s', $amount));

        // if amount is zero, stop.
        if (0 === bccomp('0', $amount)) {
            Log::warning('Amount left is zero, stop.');

            return;
        }

        // make sure we can add amount:
        if (false === $repository->canAddAmount($piggyBank, $amount)) {
            Log::warning(sprintf('Cannot add %s to piggy bank.', $amount));

            return;
        }
        Log::debug(sprintf('Will now add %s to piggy bank.', $amount));

        $repository->addAmount($piggyBank, $amount);
        $repository->createEventWithJournal($piggyBank, app('steam')->positive($amount), $journal);
    }

    /**
     * @param User $user
     *
     * @return PiggyBank|null
     */
    private function findPiggybank(User $user): ?PiggyBank
    {
        return $user->piggyBanks()->where('piggy_banks.name', $this->action->action_value)->first();
    }

    /**
     * @param array     $journalArray
     * @param PiggyBank $piggyBank
     * @param string    $amount
     */
    private function removeAmount(array $journalArray, PiggyBank $piggyBank, string $amount): void
    {
        $user = User::find($journalArray['user_id']);
        $journal = $user->transactionJournals()->find($journalArray['transaction_journal_id']);
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($journal->user);

        // how much can we remove from piggy bank?
        $toRemove = $repository->getCurrentAmount($piggyBank);
        Log::debug(sprintf('Amount is %s, max to remove is %s', $amount, $toRemove));
        // if $amount is bigger than $toRemove, shrink it.
        $amount = -1 === bccomp($amount, $toRemove) ? $amount : $toRemove;
        Log::debug(sprintf('Amount is now %s', $amount));

        // if amount is zero, stop.
        if (0 === bccomp('0', $amount)) {
            Log::warning('Amount left is zero, stop.');

            return;
        }

        // make sure we can remove amount:
        if (false === $repository->canRemoveAmount($piggyBank, $amount)) {
            Log::warning(sprintf('Cannot remove %s from piggy bank.', $amount));

            return;
        }
        Log::debug(sprintf('Will now remove %s from piggy bank.', $amount));

        $repository->removeAmount($piggyBank, $amount);
        $repository->createEventWithJournal($piggyBank, app('steam')->negative($amount), $journal);
    }

    /**
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        Log::debug(sprintf('Triggered rule action UpdatePiggybank on journal #%d', $journal['transaction_journal_id']));
        if (TransactionType::TRANSFER !== $journal['transaction_type_type']) {
            Log::info(sprintf('Journal #%d is a "%s" so skip this action.', $journal['transaction_journal_id'], $journal['transaction_type_type']));

            return false;
        }
        $user = User::find($journal['user_id']);

        $piggyBank = $this->findPiggybank($user);
        if (null === $piggyBank) {
            Log::info(sprintf('No piggy bank names "%s", cant execute action #%d of rule #%d', $this->action->action_value, $this->action->id, $this->action->rule_id));

            return false;
        }

        Log::debug(sprintf('Found piggy bank #%d ("%s")', $piggyBank->id, $piggyBank->name));

        /** @var Transaction $source */
        $source = Transaction::where('transaction_journal_id', $journal['transaction_journal_id'])->where('amount', '<', 0)->first();
        /** @var Transaction $destination */
        $destination = Transaction::where('transaction_journal_id', $journal['transaction_journal_id'])->where('amount', '>', 0)->first();

        if ((int) $source->account_id === (int) $piggyBank->account_id) {
            Log::debug('Piggy bank account is linked to source, so remove amount.');
            $this->removeAmount($journal, $piggyBank, $destination->amount);

            return true;
        }
        if ((int) $destination->account_id === (int) $piggyBank->account_id) {
            Log::debug('Piggy bank account is linked to source, so add amount.');
            $this->addAmount($journal, $piggyBank, $destination->amount);

            return true;
        }
        Log::info('Piggy bank is not linked to source or destination, so no action will be taken.');

        return true;
    }
}
