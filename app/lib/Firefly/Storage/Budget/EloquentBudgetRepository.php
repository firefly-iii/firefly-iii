<?php

namespace Firefly\Storage\Budget;

use Carbon\Carbon;

/**
 * Class EloquentBudgetRepository
 *
 * @package Firefly\Storage\Budget
 */
class EloquentBudgetRepository implements BudgetRepositoryInterface
{

    public function destroy(\Budget $budget)
    {
        $budget->delete();

        return true;
    }

    /**
     * @param $budgetId
     *
     * @return mixed
     */
    public function find($budgetId)
    {

        return \Auth::user()->budgets()->find($budgetId);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $set = \Auth::user()->budgets()->with(
            ['limits'                        => function ($q) {
                    $q->orderBy('limits.startdate', 'ASC');
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
     * @return array|mixed
     */
    public function getAsSelectList()
    {
        $list = \Auth::user()->budgets()->with(
            ['limits', 'limits.limitrepetitions']
        )->orderBy('name', 'ASC')->get();
        $return = [];
        foreach ($list as $entry) {
            $return[intval($entry->id)] = $entry->name;
        }

        return $return;
    }

    /**
     * @param Carbon $date
     * @param        $range
     *
     * @return mixed
     */
    public function getWithRepetitionsInPeriod(Carbon $date, $range)
    {


        //return $set;
    }

    /**
     * @param $data
     *
     * @return \Budget|mixed
     */
    public function store($data)
    {
        $budget = new \Budget;
        $budget->name = $data['name'];
        $budget->user()->associate(\Auth::user());
        $budget->save();

        // if limit, create limit (repetition itself will be picked up elsewhere).
        if (floatval($data['amount']) > 0) {
            $limit = new \Limit;
            $limit->budget()->associate($budget);
            $startDate = new Carbon;
            switch ($data['repeat_freq']) {
                case 'daily':
                    $startDate->startOfDay();
                    break;
                case 'weekly':
                    $startDate->startOfWeek();
                    break;
                case 'monthly':
                    $startDate->startOfMonth();
                    break;
                case 'quarterly':
                    $startDate->firstOfQuarter();
                    break;
                case 'half-year':
                    $startDate->startOfYear();
                    if (intval($startDate->format('m')) >= 7) {
                        $startDate->addMonths(6);
                    }
                    break;
                case 'yearly':
                    $startDate->startOfYear();
                    break;
            }
            $limit->startdate = $startDate;
            $limit->amount = $data['amount'];
            $limit->repeats = isset($data['repeats']) ? $data['repeats'] : 0;
            $limit->repeat_freq = $data['repeat_freq'];
            if ($limit->validate()) {
                $limit->save();
            }
        }
        if ($budget->validate()) {
            $budget->save();
        }

        return $budget;
    }

    /**
     * @param $data
     *
     * @return mixed
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