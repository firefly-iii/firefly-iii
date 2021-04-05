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

use DB;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Log;

/**
 * Class SetBudget.
 */
class SetBudget implements ActionInterface
{
    private RuleAction $action;

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
     * @inheritDoc
     */
    public function actOnArray(array $journal): bool
    {
        $user   = User::find($journal['user_id']);
        $search = $this->action->action_value;

        $budget = $user->budgets()->where('name', $search)->first();
        if (null === $budget) {
            Log::debug(
                sprintf(
                    'RuleAction SetBudget could not set budget of journal #%d to "%s" because no such budget exists.', $journal['transaction_journal_id'],
                    $search
                )
            );

            return false;
        }

        if (TransactionType::WITHDRAWAL !== $journal['transaction_type_type']) {
            Log::debug(
                sprintf(
                    'RuleAction SetBudget could not set budget of journal #%d to "%s" because journal is a %s.',
                    $journal['transaction_journal_id'],
                    $search,
                    $journal['transaction_type_type']
                )
            );

            return true;
        }

        Log::debug(
            sprintf('RuleAction SetBudget set the budget of journal #%d to budget #%d ("%s").', $journal['transaction_journal_id'], $budget->id, $budget->name)
        );

        DB::table('budget_transaction_journal')->where('transaction_journal_id', '=', $journal['transaction_journal_id'])->delete();
        DB::table('budget_transaction_journal')->insert(['transaction_journal_id' => $journal['transaction_journal_id'], 'budget_id' => $budget->id]);

        return true;
    }
}
