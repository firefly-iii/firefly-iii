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

use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;

/**
 * Class UpdatePiggybank
 */
class UpdatePiggybank implements ActionInterface
{
    private RuleAction $action;

    /**
     * TriggerInterface constructor.
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    public function actOnArray(array $journal): bool
    {
        $actionValue = $this->action->getValue($journal);

        app('log')->debug(sprintf('Triggered rule action UpdatePiggybank on journal #%d', $journal['transaction_journal_id']));

        // refresh the transaction type.
        /** @var User $user */
        $user        = User::find($journal['user_id']);

        /** @var TransactionJournal $journalObj */
        $journalObj  = $user->transactionJournals()->find($journal['transaction_journal_id']);

        $piggyBank   = $this->findPiggyBank($user, $actionValue);
        if (null === $piggyBank) {
            app('log')->info(
                sprintf('No piggy bank named "%s", cant execute action #%d of rule #%d', $actionValue, $this->action->id, $this->action->rule_id)
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_piggy', ['name' => $actionValue])));

            return false;
        }

        app('log')->debug(sprintf('Found piggy bank #%d ("%s")', $piggyBank->id, $piggyBank->name));

        /** @var Transaction $source */
        $source      = $journalObj->transactions()->where('amount', '<', 0)->first();

        /** @var Transaction $destination */
        $destination = $journalObj->transactions()->where('amount', '>', 0)->first();

        if ($source->account_id === $piggyBank->account_id) {
            app('log')->debug('Piggy bank account is linked to source, so remove amount from piggy bank.');
            throw new FireflyException('Reference the correct account here.');
            $this->removeAmount($piggyBank, $journal, $journalObj, $destination->amount);

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
        if ($destination->account_id === $piggyBank->account_id) {
            app('log')->debug('Piggy bank account is linked to source, so add amount to piggy bank.');
            $this->addAmount($piggyBank, $journal, $journalObj, $destination->amount);

            event(
                new TriggeredAuditLog(
                    $this->action->rule,
                    $journalObj,
                    'add_to_piggy',
                    null,
                    [
                        'currency_symbol'    => $journalObj->transactionCurrency->symbol,
                        'decimal_places'     => $journalObj->transactionCurrency->decimal_places,
                        'amount'             => $destination->amount,
                        'piggy'              => $piggyBank->name,
                        'piggy_id'           => $piggyBank->id,
                    ]
                )
            );

            return true;
        }
        app('log')->info(
            sprintf(
                'Piggy bank is not linked to source ("#%d") or destination ("#%d"), so no action will be taken.',
                $source->account_id,
                $destination->account_id
            )
        );
        event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_link_piggy', ['name' => $actionValue])));

        return false;
    }

    private function findPiggyBank(User $user, string $name): ?PiggyBank
    {
        return $user->piggyBanks()->where('piggy_banks.name', $name)->first();
    }

    private function removeAmount(PiggyBank $piggyBank, array $array, TransactionJournal $journal, string $amount): void
    {
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($journal->user);

        // how much can we remove from this piggy bank?
        $toRemove   = $repository->getCurrentAmount($piggyBank);
        app('log')->debug(sprintf('Amount is %s, max to remove is %s', $amount, $toRemove));

        // if $amount is bigger than $toRemove, shrink it.
        $amount     = -1 === bccomp($amount, $toRemove) ? $amount : $toRemove;
        app('log')->debug(sprintf('Amount is now %s', $amount));

        // if amount is zero, stop.
        if (0 === bccomp('0', $amount)) {
            app('log')->warning('Amount left is zero, stop.');
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_remove_zero_piggy', ['name' => $piggyBank->name])));

            return;
        }

        // make sure we can remove amount:
        throw new FireflyException('Reference the correct account here.');
        if (false === $repository->canRemoveAmount($piggyBank, $amount)) {
            app('log')->warning(sprintf('Cannot remove %s from piggy bank.', $amount));
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_remove_from_piggy', ['amount' => $amount, 'name' => $piggyBank->name])));

            return;
        }
        app('log')->debug(sprintf('Will now remove %s from piggy bank.', $amount));

        throw new FireflyException('Reference the correct account here.');
        $repository->removeAmount($piggyBank, $amount, $journal);
    }

    private function addAmount(PiggyBank $piggyBank, array $array, TransactionJournal $journal, string $amount): void
    {
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($journal->user);

        // how much can we add to the piggy bank?
        if (0 !== bccomp($piggyBank->target_amount, '0')) {
            $toAdd  = bcsub($piggyBank->target_amount, $repository->getCurrentAmount($piggyBank));
            app('log')->debug(sprintf('Max amount to add to piggy bank is %s, amount is %s', $toAdd, $amount));

            // update amount to fit:
            $amount = -1 === bccomp($amount, $toAdd) ? $amount : $toAdd;
            app('log')->debug(sprintf('Amount is now %s', $amount));
        }
        if (0 === bccomp($piggyBank->target_amount, '0')) {
            app('log')->debug('Target amount is zero, can add anything.');
        }

        // if amount is zero, stop.
        if (0 === bccomp('0', $amount)) {
            app('log')->warning('Amount left is zero, stop.');
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_add_zero_piggy', ['name' => $piggyBank->name])));

            return;
        }

        // make sure we can add amount:
        throw new FireflyException('Reference the correct account here.');
        if (false === $repository->canAddAmount($piggyBank, $amount)) {
            app('log')->warning(sprintf('Cannot add %s to piggy bank.', $amount));
            event(new RuleActionFailedOnArray($this->action, $array, trans('rules.cannot_add_to_piggy', ['amount' => $amount, 'name' => $piggyBank->name])));

            return;
        }
        app('log')->debug(sprintf('Will now add %s to piggy bank.', $amount));

        $repository->addAmount($piggyBank, $amount, $journal);
    }
}
