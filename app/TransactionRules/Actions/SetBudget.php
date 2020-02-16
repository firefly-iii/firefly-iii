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

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Log;

/**
 * Class SetBudget.
 */
class SetBudget implements ActionInterface
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
     * Set budget.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        $search = $this->action->action_value;

        $budget = $journal->user->budgets()->where('name', $search)->first();
        if (null === $budget) {
            Log::debug(sprintf('RuleAction SetBudget could not set budget of journal #%d to "%s" because no such budget exists.', $journal->id, $search));

            return false;
        }

        if (TransactionType::WITHDRAWAL !== $journal->transactionType->type) {
            Log::debug(
                sprintf(
                    'RuleAction SetBudget could not set budget of journal #%d to "%s" because journal is a %s.',
                    $journal->id,
                    $search,
                    $journal->transactionType->type
                )
            );

            return true;
        }

        Log::debug(sprintf('RuleAction SetBudget set the budget of journal #%d to budget #%d ("%s").', $journal->id, $budget->id, $budget->name));

        $journal->budgets()->sync([$budget->id]);
        $journal->touch();

        return true;
    }
}
