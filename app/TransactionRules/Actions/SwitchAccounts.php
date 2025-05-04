<?php

/**
 * ConvertToWithdrawal.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;

/**
 * Class SwitchAccounts
 */
class SwitchAccounts implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action)
    {
    }

    public function actOnArray(array $journal): bool
    {
        // make object from array (so the data is fresh).
        /** @var null|TransactionJournal $object */
        $object                        = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        if (null === $object) {
            app('log')->error(sprintf('Cannot find journal #%d, cannot switch accounts.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.no_such_journal')));

            return false;
        }
        $groupCount                    = TransactionJournal::where('transaction_group_id', $journal['transaction_group_id'])->count();
        if ($groupCount > 1) {
            app('log')->error(sprintf('Group #%d has more than one transaction in it, cannot switch accounts.', $journal['transaction_group_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.split_group')));

            return false;
        }

        $type                          = $object->transactionType->type;
        if (TransactionTypeEnum::TRANSFER->value !== $type) {
            app('log')->error(sprintf('Journal #%d is NOT a transfer (rule #%d), cannot switch accounts.', $journal['transaction_journal_id'], $this->action->rule_id));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.is_not_transfer')));

            return false;
        }

        /** @var null|Transaction $sourceTransaction */
        $sourceTransaction             = $object->transactions()->where('amount', '<', 0)->first();

        /** @var null|Transaction $destTransaction */
        $destTransaction               = $object->transactions()->where('amount', '>', 0)->first();
        if (null === $sourceTransaction || null === $destTransaction) {
            app('log')->error(sprintf('Journal #%d has no source or destination transaction (rule #%d), cannot switch accounts.', $journal['transaction_journal_id'], $this->action->rule_id));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_accounts')));

            return false;
        }
        $sourceAccountId               = $sourceTransaction->account_id;
        $destinationAccountId          = $destTransaction->account_id;
        $sourceTransaction->account_id = $destinationAccountId;
        $destTransaction->account_id   = $sourceAccountId;
        $sourceTransaction->save();
        $destTransaction->save();

        event(new TriggeredAuditLog($this->action->rule, $object, 'switch_accounts', $sourceAccountId, $destinationAccountId));

        return true;
    }
}
