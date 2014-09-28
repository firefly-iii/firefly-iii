<?php


namespace Firefly\Storage\RecurringTransaction;

use Carbon\Carbon;
use Illuminate\Queue\Jobs\Job;

/**
 * Class EloquentRecurringTransactionRepository
 *
 * @package Firefly\Storage\RecurringTransaction
 */
class EloquentRecurringTransactionRepository implements RecurringTransactionRepositoryInterface
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
     * @param \RecurringTransaction $recurringTransaction
     *
     * @return bool|mixed
     */
    public function destroy(\RecurringTransaction $recurringTransaction)
    {
        $recurringTransaction->delete();

        return true;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->_user->recurringtransactions()->get();
    }

    /**
     * @param Job   $job
     * @param array $payload
     *
     * @return mixed
     */
    public function importPredictable(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        /*
         * maybe the recurring transaction is already imported:
         */
        $oldId       = intval($payload['data']['id']);
        $description = $payload['data']['description'];
        $importEntry = $repository->findImportEntry($importMap, 'RecurringTransaction', $oldId);

        /*
         * if so, delete job and return:
         */
        if (!is_null($importEntry)) {
            \Log::debug('Already imported recurring transaction #' . $payload['data']['id']);

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }

        // try to find related recurring transaction:
        $recurringTransaction = $this->findByName($payload['data']['description']);
        if (is_null($recurringTransaction)) {
            $amount = floatval($payload['data']['amount']);
            $pct    = intval($payload['data']['pct']);

            $set = [
                'name'        => $description,
                'match'       => join(',', explode(' ', $description)),
                'amount_min'  => $amount * ($pct / 100) * -1,
                'amount_max'  => $amount * (1 + ($pct / 100)) * -1,
                'date'        => date('Y-m-') . $payload['data']['dom'],
                'repeat_freq' => 'monthly',
                'active'      => intval($payload['data']['inactive']) == 1 ? 0 : 1,
                'automatch'   => 1,
            ];

            $recurringTransaction = $this->store($set);
            $this->store($importMap, 'RecurringTransaction', $oldId, $recurringTransaction->id);
            \Log::debug('Imported predictable ' . $description);
        } else {
            $this->store($importMap, 'RecurringTransaction', $oldId, $recurringTransaction->id);
            \Log::debug('Already had predictable ' . $description);
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

    public function findByName($name)
    {
        return $this->_user->recurringtransactions()->where('name', 'LIKE', '%' . $name . '%')->first();
    }

    /**
     * @param $data
     *
     * @return mixed|\RecurringTransaction
     */
    public function store($data)
    {
        $recurringTransaction = new \RecurringTransaction(
            [
                'user_id'     => $this->_user->id,
                'name'        => $data['name'],
                'match'       => join(' ', explode(',', $data['match'])),
                'amount_max'  => floatval($data['amount_max']),
                'amount_min'  => floatval($data['amount_min']),
                'date'        => new Carbon($data['date']),
                'active'      => isset($data['active']) ? intval($data['active']) : 0,
                'automatch'   => isset($data['automatch']) ? intval($data['automatch']) : 0,
                'skip'        => isset($data['skip']) ? intval($data['skip']) : 0,
                'repeat_freq' => $data['repeat_freq'],
            ]
        );

        // both amounts zero?:
        if ($recurringTransaction->amount_max == 0 && $recurringTransaction->amount_min == 0) {
            $recurringTransaction->errors()->add('amount_max', 'Amount max and min cannot both be zero.');

            return $recurringTransaction;
        }

        if ($recurringTransaction->amount_max < $recurringTransaction->amount_min) {
            $recurringTransaction->errors()->add('amount_max', 'Amount max must be more than amount min.');
            return $recurringTransaction;
        }

        if ($recurringTransaction->amount_min > $recurringTransaction->amount_max) {
            $recurringTransaction->errors()->add('amount_max', 'Amount min must be less than amount max.');
            return $recurringTransaction;
        }

        if($recurringTransaction->date < Carbon::now()) {
            $recurringTransaction->errors()->add('date', 'Must be in the future.');
            return $recurringTransaction;
        }


        if ($recurringTransaction->validate()) {
            $recurringTransaction->save();
        }

        return $recurringTransaction;
    }

    /**
     * @param \RecurringTransaction $recurringTransaction
     * @param                       $data
     *
     * @return mixed|void
     */
    public function update(\RecurringTransaction $recurringTransaction, $data)
    {
        $recurringTransaction->name       = $data['name'];
        $recurringTransaction->match      = join(' ', explode(',', $data['match']));
        $recurringTransaction->amount_max = floatval($data['amount_max']);
        $recurringTransaction->amount_min = floatval($data['amount_min']);

        // both amounts zero:
        if ($recurringTransaction->amount_max == 0 && $recurringTransaction->amount_min == 0) {
            $recurringTransaction->errors()->add('amount_max', 'Amount max and min cannot both be zero.');

            return $recurringTransaction;
        }
        $recurringTransaction->date        = new Carbon($data['date']);
        $recurringTransaction->active      = isset($data['active']) ? intval($data['active']) : 0;
        $recurringTransaction->automatch   = isset($data['automatch']) ? intval($data['automatch']) : 0;
        $recurringTransaction->skip        = isset($data['skip']) ? intval($data['skip']) : 0;
        $recurringTransaction->repeat_freq = $data['repeat_freq'];

        if ($recurringTransaction->validate()) {
            $recurringTransaction->save();
        }

        return $recurringTransaction;

    }

}