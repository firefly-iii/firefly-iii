<?php
/**
 * BudgetRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Services\Internal\Destroy\BudgetDestroyService;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class BudgetRepository.
 *
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @return bool
     */
    public function cleanupBudgets(): bool
    {
        // delete limits with amount 0:
        try {
            BudgetLimit::where('amount', 0)->delete();
        } catch (Exception $e) {
            Log::debug(sprintf('Could not delete budget limit: %s', $e->getMessage()));
        }
        $budgets = $this->getActiveBudgets();
        /**
         * @var int    $index
         * @var Budget $budget
         */
        foreach ($budgets as $index => $budget) {
            $budget->order = $index + 1;
            $budget->save();
        }

        return true;
    }

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool
    {
        /** @var BudgetDestroyService $service */
        $service = app(BudgetDestroyService::class);
        $service->destroy($budget);

        return true;
    }

    /**
     * @param int|null    $budgetId
     * @param string|null $budgetName
     *
     * @return Budget|null
     */
    public function findBudget(?int $budgetId, ?string $budgetName): ?Budget
    {
        Log::debug('Now in findBudget()');
        Log::debug(sprintf('Searching for budget with ID #%d...', $budgetId));
        $result = $this->findNull((int)$budgetId);
        if (null === $result && null !== $budgetName && '' !== $budgetName) {
            Log::debug(sprintf('Searching for budget with name %s...', $budgetName));
            $result = $this->findByName((string)$budgetName);
        }
        if (null !== $result) {
            Log::debug(sprintf('Found budget #%d: %s', $result->id, $result->name));
        }
        Log::debug(sprintf('Found result is null? %s', var_export(null === $result, true)));

        return $result;
    }

    /**
     * Find budget by name.
     *
     * @param string|null $name
     *
     * @return Budget|null
     */
    public function findByName(?string $name): ?Budget
    {
        if (null === $name) {
            return null;
        }
        $query = sprintf('%%%s%%', $name);

        return $this->user->budgets()->where('name', 'LIKE', $query)->first();
    }

    /**
     * Find a budget or return NULL
     *
     * @param int $budgetId |null
     *
     * @return Budget|null
     */
    public function findNull(int $budgetId = null): ?Budget
    {
        if (null === $budgetId) {
            return null;
        }

        return $this->user->budgets()->find($budgetId);
    }

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     *
     */
    public function firstUseDate(Budget $budget): ?Carbon
    {
        $oldest  = null;
        $journal = $budget->transactionJournals()->orderBy('date', 'ASC')->first();
        if (null !== $journal) {
            $oldest = $journal->date < $oldest ? $journal->date : $oldest;
        }

        $transaction = $budget
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transactions.*', 'transaction_journals.date']);
        if (null !== $transaction) {
            $carbon = new Carbon($transaction->date);
            $oldest = $carbon < $oldest ? $carbon : $oldest;
        }

        return $oldest;
    }

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 1)
                          ->orderBy('order', 'ASC')
                          ->orderBy('name', 'ASC')
                          ->get();

        return $set;
    }

    /**
     * @return Collection
     */
    public function getBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->orderBy('order', 'DESC')
                          ->orderBy('name', 'ASC')->get();

        return $set;
    }

    /**
     * Get all budgets with these ID's.
     *
     * @param array $budgetIds
     *
     * @return Collection
     */
    public function getByIds(array $budgetIds): Collection
    {
        return $this->user->budgets()->whereIn('id', $budgetIds)->get();
    }

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->orderBy('order', 'DESC')
                          ->orderBy('name', 'ASC')->where('active', 0)->get();

        return $set;
    }

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchBudget(string $query): Collection
    {

        $search = $this->user->budgets();
        if ('' !== $query) {
            $search->where('name', 'LIKE', sprintf('%%%s%%', $query));
        }

        return $search->get();
    }

    /**
     * @param Budget $budget
     * @param int    $order
     */
    public function setBudgetOrder(Budget $budget, int $order): void
    {
        $budget->order = $order;
        $budget->save();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget
    {
        $newBudget = new Budget(
            [
                'user_id' => $this->user->id,
                'name'    => $data['name'],
            ]
        );
        $newBudget->save();

        return $newBudget;
    }

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget
    {
        $oldName        = $budget->name;
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();
        $this->updateRuleTriggers($oldName, $data['name']);
        $this->updateRuleActions($oldName, $data['name']);
        app('preferences')->mark();

        return $budget;
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function updateRuleActions(string $oldName, string $newName): void
    {
        $types   = ['set_budget',];
        $actions = RuleAction::leftJoin('rules', 'rules.id', '=', 'rule_actions.rule_id')
                             ->where('rules.user_id', $this->user->id)
                             ->whereIn('rule_actions.action_type', $types)
                             ->where('rule_actions.action_value', $oldName)
                             ->get(['rule_actions.*']);
        Log::debug(sprintf('Found %d actions to update.', $actions->count()));
        /** @var RuleAction $action */
        foreach ($actions as $action) {
            $action->action_value = $newName;
            $action->save();
            Log::debug(sprintf('Updated action %d: %s', $action->id, $action->action_value));
        }
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function updateRuleTriggers(string $oldName, string $newName): void
    {
        $types    = ['budget_is',];
        $triggers = RuleTrigger::leftJoin('rules', 'rules.id', '=', 'rule_triggers.rule_id')
                               ->where('rules.user_id', $this->user->id)
                               ->whereIn('rule_triggers.trigger_type', $types)
                               ->where('rule_triggers.trigger_value', $oldName)
                               ->get(['rule_triggers.*']);
        Log::debug(sprintf('Found %d triggers to update.', $triggers->count()));
        /** @var RuleTrigger $trigger */
        foreach ($triggers as $trigger) {
            $trigger->trigger_value = $newName;
            $trigger->save();
            Log::debug(sprintf('Updated trigger %d: %s', $trigger->id, $trigger->trigger_value));
        }
    }
}
