<?php

namespace Firefly\Storage\Budget;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\Jobs\Job;

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
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importBudget(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        /*
         * maybe Budget is already imported:
         */
        $importEntry = $repository->findImportEntry($importMap, 'Budget', intval($payload['data']['id']));

        /*
         * if so, delete job and return:
         */
        if (!is_null($importEntry)) {
            \Log::debug('Already imported budget ' . $payload['data']['name']);

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }

        /*
         * maybe Budget is already imported.
         */
        $budget = $this->findByName($payload['data']['name']);

        if (is_null($budget)) {
            /*
             * Not imported yet.
             */
            $budget = $this->store($payload['data']);
            $repository->store($importMap, 'Budget', $payload['data']['id'], $budget->id);
            \Log::debug('Imported budget "' . $payload['data']['name'] . '".');
        } else {
            /*
             * already imported.
             */
            $repository->store($importMap, 'Budget', $payload['data']['id'], $budget->id);
            \Log::debug('Already had budget "' . $payload['data']['name'] . '".');
        }

        // update map:
        $importMap->jobsdone++;
        $importMap->save();

        // delete job.
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

    /**
     * @param $budgetName
     *
     * @return \Budget|null
     */
    public function findByName($budgetName)
    {

        return $this->_user->budgets()->whereName($budgetName)->first();
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
     * Takes a transfer/budget component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransfer(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        if ($job->attempts() > 10) {
            \Log::error('Never found budget/transfer combination "' . $payload['data']['transfer_id'] . '"');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }


        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $journals->overruleUser($user);

        /*
         * Prep some vars from the payload
         */
        $transferId = intval($payload['data']['transfer_id']);
        $componentId   = intval($payload['data']['component_id']);

        /*
         * Find the import map for both:
         */
        $budgetMap      = $repository->findImportEntry($importMap, 'Budget', $componentId);
        $transferMap = $repository->findImportEntry($importMap, 'Transfer', $transferId);

        /*
         * Either may be null:
         */
        if (is_null($budgetMap) || is_null($transferMap)) {
            \Log::notice('No map found in budget/transfer mapper. Release.');
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
         * Find the budget and the transaction:
         */
        $budget = $this->find($budgetMap->new);
        /** @var \TransactionJournal $journal */
        $journal = $journals->find($transferMap->new);

        /*
         * If either is null, release:
         */
        if (is_null($budget) || is_null($journal)) {
            \Log::notice('Map is incorrect in budget/transfer mapper. Release.');
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
         * Update journal to have budget:
         */
        $journal->budgets()->save($budget);
        $journal->save();
        \Log::debug('Connected budget "' . $budget->name . '" to journal "' . $journal->description . '"');

        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed


        return;
    }

    /**
     * Takes a transaction/budget component and updates the transaction journal to match.
     *
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importUpdateTransaction(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        if ($job->attempts() > 10) {
            \Log::error('Never found budget/transaction combination "' . $payload['data']['transaction_id'] . '"');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }


        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $journals->overruleUser($user);

        /*
         * Prep some vars from the payload
         */
        $transactionId = intval($payload['data']['transaction_id']);
        $componentId   = intval($payload['data']['component_id']);

        /*
         * Find the import map for both:
         */
        $budgetMap      = $repository->findImportEntry($importMap, 'Budget', $componentId);
        $transactionMap = $repository->findImportEntry($importMap, 'Transaction', $transactionId);

        /*
         * Either may be null:
         */
        if (is_null($budgetMap) || is_null($transactionMap)) {
            \Log::notice('No map found in budget/transaction mapper. Release.');
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
         * Find the budget and the transaction:
         */
        $budget = $this->find($budgetMap->new);
        /** @var \TransactionJournal $journal */
        $journal = $journals->find($transactionMap->new);

        /*
         * If either is null, release:
         */
        if (is_null($budget) || is_null($journal)) {
            \Log::notice('Map is incorrect in budget/transaction mapper. Release.');
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
         * Update journal to have budget:
         */
        $journal->budgets()->save($budget);
        $journal->save();
        \Log::debug('Connected budget "' . $budget->name . '" to journal "' . $journal->description . '"');

        $importMap->jobsdone++;
        $importMap->save();

        $job->delete(); // count fixed


        return;
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
        return $set;
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