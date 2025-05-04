<?php

/**
 * UpdatePiggyBank.php
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

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

class UpdatePiggyBank implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        $actionValue = $this->action->getValue($journal);

        Log::debug(sprintf('Triggered rule action UpdatePiggyBank on journal #%d', $journal['transaction_journal_id']));

        // refresh the transaction type.
        /** @var User $user */
        $user        = User::find($journal['user_id']);

        /** @var TransactionJournal $journalObj */
        $journalObj  = $user->transactionJournals()->find($journal['transaction_journal_id']);

        $piggyBank   = $this->findPiggyBank($user, $actionValue);
        if (null === $piggyBank) {
            Log::info(
                sprintf('No piggy bank named "%s", cant execute action #%d of rule #%d', $actionValue, $this->action->id, $this->action->rule_id)
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_piggy', ['name' => $actionValue])));

            return false;
        }

        Log::debug(sprintf('Found piggy bank #%d ("%s")', $piggyBank->id, $piggyBank->name));

        /** @var Transaction $destination */
        $destination = $journalObj->transactions()->where('amount', '>', 0)->first();

        $accounts    = $this->getAccounts($journalObj);
        Log::debug(sprintf('Source account is #%d: "%s"', $accounts['source']->id, $accounts['source']->name));
        Log::debug(sprintf('Destination account is #%d: "%s"', $accounts['destination']->id, $accounts['source']->name));

        // if connected to source but not to destination, needs to be removed from source account connected to piggy bank.
        if ($this->isConnected($piggyBank, $accounts['source']) && !$this->isConnected($piggyBank, $accounts['destination'])) {
            Log::debug('Piggy bank account is linked to source, so remove amount from piggy bank.');
            $this->removeAmount($piggyBank, $journal, $journalObj, $accounts['source'], $destination->amount);
            event(
                new TriggeredAuditLog(
                    $this->action->rule,
                    $journalObj,
                    'remove_from_piggy',
                    null,
                    [
                        'currency_symbol' => $journalObj->transactionCurrency->symbol,
                        'decimal_places'  => $journalObj->transactionCurrency->decimal_places,
                        'amount'          => $destination->amount,
                        'piggy'           => $piggyBank->name,
                    ]
                )
            );

            return true;
        }

        // if connected to destination but not to source, needs to be removed from source account connected to piggy bank.
        if (!$this->isConnected($piggyBank, $accounts['source']) && $this->isConnected($piggyBank, $accounts['destination'])) {
            Log::debug('Piggy bank account is linked to source, so add amount to piggy bank.');
            $this->addAmount($piggyBank, $journal, $journalObj, $accounts['destination'], $destination->amount);

            event(
                new TriggeredAuditLog(
                    $this->action->rule,
                    $journalObj,
                    'add_to_piggy',
                    null,
                    [
                        'currency_symbol' => $journalObj->transactionCurrency->symbol,
                        'decimal_places'  => $journalObj->transactionCurrency->decimal_places,
                        'amount'          => $destination->amount,
                        'piggy'           => $piggyBank->name,
                        'piggy_id'        => $piggyBank->id,
                    ]
                )
            );

            return true;
        }
        if ($this->isConnected($piggyBank, $accounts['source']) && $this->isConnected($piggyBank, $accounts['destination'])) {
            Log::info(sprintf('Piggy bank is linked to BOTH source ("#%d") and destination ("#%d"), so no action will be taken.', $accounts['source']->id, $accounts['destination']->id));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_link_piggy', ['name' => $actionValue])));

            return false;
        }
        Log::info(sprintf('Piggy bank is not linked to source ("#%d") or destination ("#%d"), so no action will be taken.', $accounts['source']->id, $accounts['destination']->id));
        event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_link_piggy', ['name' => $actionValue])));

        return false;
    }

    private function findPiggyBank(User $user, string $name): ?PiggyBank
    {
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($user);

        return $repository->findByName($name);
    }

    private function removeAmount(PiggyBank $piggyBank, array $array, TransactionJournal $journal, Account $account, string $amount): void
    {
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($journal->user);

        // how much can we remove from this piggy bank?
        $toRemove   = $repository->getCurrentAmount($piggyBank, $account);
        Log::debug(sprintf('Amount is %s, max to remove is %s', $amount, $toRemove));

        // if $amount is bigger than $toRemove, shrink it.
        $amount     = -1 === bccomp($amount, $toRemove) ? $amount : $toRemove;
        Log::debug(sprintf('Amount is now %s', $amount));

        // if amount is zero, stop.
        if (0 === bccomp('0', $amount)) {
            Log::warning('Amount left is zero, stop.');
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_remove_zero_piggy', ['name' => $piggyBank->name])));

            return;
        }

        if (false === $repository->canRemoveAmount($piggyBank, $account, $amount)) {
            Log::warning(sprintf('Cannot remove %s from piggy bank.', $amount));
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_remove_from_piggy', ['amount' => $amount, 'name' => $piggyBank->name])));

            return;
        }
        Log::debug(sprintf('Will now remove %s from piggy bank.', $amount));

        $repository->removeAmount($piggyBank, $account, $amount, $journal);
    }

    private function addAmount(PiggyBank $piggyBank, array $array, TransactionJournal $journal, Account $account, string $amount): void
    {
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($journal->user);

        // how much can we add to the piggy bank?
        if (0 !== bccomp($piggyBank->target_amount, '0')) {
            $toAdd  = bcsub($piggyBank->target_amount, $repository->getCurrentAmount($piggyBank, $account));
            Log::debug(sprintf('Max amount to add to piggy bank is %s, amount is %s', $toAdd, $amount));

            // update amount to fit:
            $amount = -1 === bccomp($amount, $toAdd) ? $amount : $toAdd;
            Log::debug(sprintf('Amount is now %s', $amount));
        }
        if (0 === bccomp($piggyBank->target_amount, '0')) {
            Log::debug('Target amount is zero, can add anything.');
        }

        // if amount is zero, stop.
        if (0 === bccomp('0', $amount)) {
            Log::warning('Amount left is zero, stop.');
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_add_zero_piggy', ['name' => $piggyBank->name])));

            return;
        }

        if (false === $repository->canAddAmount($piggyBank, $account, $amount)) {
            Log::warning(sprintf('Cannot add %s to piggy bank.', $amount));
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_add_to_piggy', ['amount' => $amount, 'name' => $piggyBank->name])));

            return;
        }
        Log::debug(sprintf('Will now add %s to piggy bank.', $amount));

        $repository->addAmount($piggyBank, $account, $amount, $journal);
    }

    private function getAccounts(TransactionJournal $journal): array
    {
        return [
            'source'      => $journal->transactions()->where('amount', '<', '0')->first()?->account,
            'destination' => $journal->transactions()->where('amount', '>', '0')->first()?->account,
        ];
    }

    private function isConnected(PiggyBank $piggyBank, ?Account $link): bool
    {
        if (null === $link) {
            return false;
        }
        foreach ($piggyBank->accounts as $account) {
            if ($account->id === $link->id) {
                return true;
            }
        }
        Log::debug(sprintf('Piggy bank is not connected to account #%d "%s"', $link->id, $link->name));

        return false;
    }
}
