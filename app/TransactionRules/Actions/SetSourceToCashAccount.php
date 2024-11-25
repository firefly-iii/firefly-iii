<?php

/*
 * SetsourceToCashAccount.php
 * Copyright (c) 2023 james@firefly-iii.org
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
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;

/**
 * Class SetSourceToCashAccount
 */
class SetSourceToCashAccount implements ActionInterface
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
        /** @var User $user */
        $user        = User::find($journal['user_id']);

        /** @var null|TransactionJournal $object */
        $object      = $user->transactionJournals()->find((int)$journal['transaction_journal_id']);
        $repository  = app(AccountRepositoryInterface::class);

        if (null === $object) {
            app('log')->error('Could not find journal.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_such_journal')));

            return false;
        }
        $type        = $object->transactionType->type;
        if (TransactionType::DEPOSIT !== $type) {
            app('log')->error('Transaction must be deposit.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.not_deposit')));

            return false;
        }

        // find cash account
        $repository->setUser($user);
        $cashAccount = $repository->getCashAccount();

        // new source account must be different from the current destination account:
        /** @var null|Transaction $destination */
        $destination = $object->transactions()->where('amount', '>', 0)->first();
        if (null === $destination) {
            app('log')->error('Could not find destination transaction.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_destination_transaction')));

            return false;
        }
        // account must not be deleted (in the meantime):
        if (null === $destination->account) {
            app('log')->error('Could not find destination transaction account.');
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_destination_transaction_account')));

            return false;
        }
        if ($cashAccount->id === $destination->account_id) {
            app('log')->error(
                sprintf(
                    'New source account ID #%d and current destination account ID #%d are the same. Do nothing.',
                    $cashAccount->id,
                    $destination->account_id
                )
            );

            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.already_has_source', ['name' => $cashAccount->name])));

            return false;
        }

        event(new TriggeredAuditLog($this->action->rule, $object, 'set_source', null, $cashAccount->name));

        // update destination transaction with new destination account:
        \DB::table('transactions')
            ->where('transaction_journal_id', '=', $object->id)
            ->where('amount', '<', 0)
            ->update(['account_id' => $cashAccount->id])
        ;

        app('log')->debug(sprintf('Updated journal #%d (group #%d) and gave it new source account ID.', $object->id, $object->transaction_group_id));

        return true;
    }
}
