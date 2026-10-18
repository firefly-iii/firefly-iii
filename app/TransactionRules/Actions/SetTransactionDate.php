<?php

/**
 * SetBudget.php
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

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\Model\TransactionGroup\TransactionGroupRequestsAuditLogEntry;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class SetTransactionDate.
 */
class SetTransactionDate implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(
        private readonly RuleAction $action
    ) {}

    public function actOnArray(array $journal): bool
    {
        /** @var User $user */
        $user          = User::find($journal['user_id']);
        $search        = $this->action->getValue($journal);

        if('' === $search) {
            Log::debug(sprintf('RuleAction SetTransactionDate failed because the date is empty for journal #%d.', $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.invalid_date')));
            return false;
        }
        // needs to be parseable date:
        try {
            $carbon = Carbon::parse($search);
        } catch(InvalidFormatException $e) {
            Log::debug(sprintf('RuleAction SetTransactionDate failed because the date "%s" is invalid for journal #%d.', $search, $journal['transaction_journal_id']));
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.invalid_date')));
            return false;
        }

        /** @var TransactionJournal $object */
        $object        = $user->transactionJournals()->find($journal['transaction_journal_id']);

        Log::debug(sprintf('RuleAction SetTransactionDate set the date of journal #%d to "%s".', $journal['transaction_journal_id'], $carbon->toW3cString()));
        $oldDate = clone $object->date;
        $object->date = clone $carbon;
        $object->save();


        event(new TransactionGroupRequestsAuditLogEntry($this->action->rule, $object, 'set_transaction_date', $oldDate->toW3cString(), $carbon->toW3cString()));

        return true;
    }
}
