<?php

namespace Firefly\Storage\Budget;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class EloquentBudgetRepository
 *
 * @package Firefly\Storage\Budget
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 */
class EloquentBudgetRepository implements BudgetRepositoryInterface
{
    protected $_user = null;

    /**
     *
     */
    public function __construct()
    {
        $this->_user = \Auth::user();
    }

    /**
     * @param \Budget $budget
     *
     * @return bool
     */
    public function destroy(\Budget $budget)
    {
        $budget->delete();

        return true;
    }

    /**
     * @param $budgetId
     *
     * @return \Budget|null
     */
    public function find($budgetId)
    {

        return $this->_user->budgets()->find($budgetId);
    }

    /**
     * @param $budgetName
     * @return \Budget|null
     */
    public function findByName($budgetName)
    {

        return $this->_user->budgets()->whereName($budgetName)->first();
    }

    /**
     * @return Collection
     */
    public function get()
    {
        $set = $this->_user->budgets()->with(
                           ['limits'                        => function ($q) {
                                   $q->orderBy('limits.startdate', 'DESC');
                               }, 'limits.limitrepetitions' => function ($q) {
                                   $q->orderBy('limit_repetitions.startdate', 'ASC');
                               }]
        )->orderBy('name', 'ASC')->get();
        foreach ($set as $budget) {
            foreach ($budget->limits as $limit) {
                foreach ($limit->limitrepetitions as $rep) {
                    $rep->left = $rep->left();
                }
            }
        }

        return $set;
    }

    /**
     * @return array
     */
    public function getAsSelectList()
    {
        $list   = $this->_user->budgets()->with(
                              ['limits', 'limits.limitrepetitions']
        )->orderBy('name', 'ASC')->get();
        $return = [];
        foreach ($list as $entry) {
            $return[intval($entry->id)] = $entry->name;
        }

        return $return;
    }

    /**
     * @param \User $user
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

    /**
     * @param $data
     *
     * @return \Budget
     */
    public function store($data)
    {
        $budget       = new \Budget;
        $budget->name = $data['name'];
        $budget->user()->associate($this->_user);
        $budget->save();

        // if limit, create limit (repetition itself will be picked up elsewhere).
        if (isset($data['amount']) && floatval($data['amount']) > 0) {
            $startDate = new Carbon;
            $limitData = [
                'budget_id' => $budget->id,
                'startdate' => $startDate->format('Y-m-d'),
                'period'    => $data['repeat_freq'],
                'amount'    => floatval($data['amount']),
                'repeats'   => 0
            ];
            /** @var \Firefly\Storage\Limit\LimitRepositoryInterface $limitRepository */
            $limitRepository = \App::make('Firefly\Storage\Limit\LimitRepositoryInterface');
            $limitRepository->overruleUser($this->_user);
            $limit = $limitRepository->store($limitData);
            \Event::fire('limits.store', [$limit]);
        }
        
        if ($budget->validate()) {
            $budget->save();
        }

        return $budget;
    }

    /**
     * @param \Budget $budget
     * @param         $data
     *
     * @return \Budget|mixed
     */
    public function update(\Budget $budget, $data)
    {
        // update account accordingly:
        $budget->name = $data['name'];
        if ($budget->validate()) {
            $budget->save();
        }

        return $budget;
    }

} 