<?php
/**
 * SetBudget.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Rules\Actions;


use FireflyIII\Models\Budget;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Log;

/**
 * Class SetBudget
 *
 * @package FireflyIII\Rules\Action
 */
class SetBudget implements ActionInterface
{

    private $action;
    private $journal;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction         $action
     * @param TransactionJournal $journal
     */
    public function __construct(RuleAction $action, TransactionJournal $journal)
    {
        $this->action  = $action;
        $this->journal = $journal;
    }

    /**
     * @return bool
     */
    public function act()
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $search     = $this->action->action_value;
        $budgets    = $repository->getActiveBudgets();
        $budget     = $budgets->filter(
            function (Budget $current) use ($search) {
                return $current->name == $search;
            }
        )->first();
        if (!is_null($budget)) {
            Log::debug('Will set budget "' . $search . '" (#' . $budget->id . ') on journal #' . $this->journal->id . '.');
            $this->journal->budgets()->sync([$budget->id]);
        } else {
            Log::debug('Could not find budget "' . $search . '". Failed.');
        }

        return true;
    }
}