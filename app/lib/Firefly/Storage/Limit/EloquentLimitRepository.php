<?php

namespace Firefly\Storage\Limit;


use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;

/**
 * Class EloquentLimitRepository
 *
 * @package Firefly\Storage\Limit
 */
class EloquentLimitRepository implements LimitRepositoryInterface
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
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importLimit(Job $job, array $payload)
    {

        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        if ($job->attempts() > 10) {
            \Log::error(
                'No budget found for limit #' . $payload['data']['id'] . '. Prob. for another component. KILL!'
            );

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed.
            return;
        }

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgets */
        $budgets = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->overruleUser($user);

        /*
         * Find the budget this limit is part of:
         */
        $importEntry = $repository->findImportEntry($importMap, 'Budget', intval($payload['data']['component_id']));

        /*
         * There is no budget (yet?)
         */
        if (is_null($importEntry)) {
            $componentId = intval($payload['data']['component_id']);
            \Log::warning('Budget #' . $componentId . ' not found. Requeue import job.');
            if(\Config::get('queue.default') == 'sync') {
                $importMap->jobsdone++;
                $importMap->save();
                $job->delete(); // count fixed
            } else {
                $job->release(300); // proper release.
            }
            return;
        }

        /*
         * Find budget import limit is for:
         */
        $budget = $budgets->find($importEntry->new);
        if (!is_null($budget)) {
            /*
             * Is actual limit already imported?
             */
            $limit = $this->findByBudgetAndDate($budget, new Carbon($payload['data']['date']));
            if (is_null($limit)) {
                /*
                 * It isn't imported yet.
                 */
                $payload['data']['budget_id'] = $budget->id;
                $payload['data']['startdate'] = $payload['data']['date'];
                $payload['data']['period']    = 'monthly';
                /*
                 * Store limit, and fire event for LimitRepetition.
                 */
                $limit = $this->store($payload['data']);
                $repository->store($importMap, 'Limit', $payload['data']['id'], $limit->id);
                \Event::fire('limits.store', [$limit]);
                \Log::debug('Imported limit for budget ' . $budget->name);
            } else {
                /*
                 * Limit already imported:
                 */
                $repository->store($importMap, 'Budget', $payload['data']['id'], $limit->id);
            }
        } else {
            \Log::error(print_r($importEntry,true));
            \Log::error('Cannot import limit! Big bad error!');
        }

        // update map:
        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed
    }

    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        return true;
    }

    public function findByBudgetAndDate(\Budget $budget, Carbon $date)
    {
        return \Limit::whereComponentId($budget->id)->where('startdate', $date->format('Y-m-d'))->first();
    }

    /**
     * @param $data
     *
     * @return \Limit
     */
    public function store($data)
    {
        $budget = \Budget::find($data['budget_id']);
        if (is_null($budget)) {
            \Session::flash('error', 'No such budget.');

            return new \Limit;
        }
        // set the date to the correct start period:
        $date = new Carbon($data['startdate']);
        switch ($data['period']) {
            case 'daily':
                $date->startOfDay();
                break;
            case 'weekly':
                $date->startOfWeek();
                break;
            case 'monthly':
                $date->startOfMonth();
                break;
            case 'quarterly':
                $date->firstOfQuarter();
                break;
            case 'half-year':

                if (intval($date->format('m')) >= 7) {
                    $date->startOfYear();
                    $date->addMonths(6);
                } else {
                    $date->startOfYear();
                }
                break;
            case 'yearly':
                $date->startOfYear();
                break;
        }
        // find existing:
        $count = \Limit::
            leftJoin('components', 'components.id', '=', 'limits.component_id')->where(
                'components.user_id', $this->_user->id
            )->where('startdate', $date->format('Y-m-d'))->where('component_id', $data['budget_id'])->where(
                'repeat_freq', $data['period']
            )->count();
        if ($count > 0) {
            \Session::flash('error', 'There already is an entry for these parameters.');

            return new \Limit;
        }
        // create new limit:
        $limit = new \Limit;
        $limit->budget()->associate($budget);
        $limit->startdate   = $date;
        $limit->amount      = floatval($data['amount']);
        $limit->repeats     = isset($data['repeats']) ? intval($data['repeats']) : 0;
        $limit->repeat_freq = $data['period'];
        if (!$limit->save()) {
            \Session::flash('error', 'Could not save: ' . $limit->errors()->first());
        }

        return $limit;
    }

    /**
     * @param \Limit $limit
     *
     * @return bool
     */
    public function destroy(\Limit $limit)
    {
        $limit->delete();

        return true;
    }

    /**
     * @param \Budget $budget
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     */
    public function getTJByBudgetAndDateRange(\Budget $budget, Carbon $start, Carbon $end)
    {
        $result = $budget->transactionjournals()->with('transactions')->after($start)->before($end)->get();

        return $result;

    }

    /**
     * @param \Limit $limit
     * @param        $data
     *
     * @return mixed|void
     */
    public function update(\Limit $limit, $data)
    {
        $limit->startdate   = new Carbon($data['startdate']);
        $limit->repeat_freq = $data['period'];
        $limit->repeats     = isset($data['repeats']) && $data['repeats'] == '1' ? 1 : 0;
        $limit->amount      = floatval($data['amount']);

        $limit->save();

        return $limit;
    }

} 