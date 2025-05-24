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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Facades\DB;

/**
 * Class SetBudget.
 */
class SetBudget implements ActionInterface
{
    /**
     * TriggerInterface constructor.
     */
    public function __construct(private readonly RuleAction $action) {}

    public function actOnArray(array $journal): bool
    {
        /** @var User $user */
        $user          = User::find($journal['user_id']);
        $search        = $this->action->getValue($journal);

        $budget        = $user->budgets()->where('name', $search)->first();
        if (null === $budget) {
            app('log')->debug(
                sprintf(
                    'RuleAction SetBudget could not set budget of journal #%d to "%s" because no such budget exists.',
                    $journal['transaction_journal_id'],
                    $search
                )
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_find_budget', ['name' => $search])));

            return false;
        }

        if (TransactionTypeEnum::WITHDRAWAL->value !== $journal['transaction_type_type']) {
            app('log')->debug(
                sprintf(
                    'RuleAction SetBudget could not set budget of journal #%d to "%s" because journal is a %s.',
                    $journal['transaction_journal_id'],
                    $search,
                    $journal['transaction_type_type']
                )
            );
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.cannot_set_budget', ['type' => $journal['transaction_type_type'], 'name' => $search])));

            return false;
        }

        // find previous budget
        /** @var TransactionJournal $object */
        $object        = $user->transactionJournals()->find($journal['transaction_journal_id']);
        $oldBudget     = $object->budgets()->first();
        $oldBudgetName = $oldBudget?->name;
        if ((int) $oldBudget?->id === $budget->id) {
            event(new RuleActionFailedOnArray($this->action, $journal, trans('rules.already_linked_to_budget', ['name' => $budget->name])));

            return false;
        }

        app('log')->debug(
            sprintf('RuleAction SetBudget set the budget of journal #%d to budget #%d ("%s").', $journal['transaction_journal_id'], $budget->id, $budget->name)
        );

        DB::table('budget_transaction_journal')->where('transaction_journal_id', '=', $journal['transaction_journal_id'])->delete();
        DB::table('budget_transaction_journal')->insert(['transaction_journal_id' => $journal['transaction_journal_id'], 'budget_id' => $budget->id]);

        /** @var TransactionJournal $object */
        $object        = TransactionJournal::where('user_id', $journal['user_id'])->find($journal['transaction_journal_id']);
        event(new TriggeredAuditLog($this->action->rule, $object, 'set_budget', $oldBudgetName, $budget->name));

        return true;
    }
}
