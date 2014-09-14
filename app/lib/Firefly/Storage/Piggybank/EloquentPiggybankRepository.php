<?php

namespace Firefly\Storage\Piggybank;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Collection;


/**
 * Class EloquentLimitRepository
 *
 * @package Firefly\Storage\Limit
 */
class EloquentPiggybankRepository implements PiggybankRepositoryInterface
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
    public function importPiggybank(Job $job, array $payload)
    {
        /** @var \Firefly\Storage\Import\ImportRepositoryInterface $repository */
        $repository = \App::make('Firefly\Storage\Import\ImportRepositoryInterface');

        /** @var \Importmap $importMap */
        $importMap = $repository->findImportmap($payload['mapID']);
        $user      = $importMap->user;
        $this->overruleUser($user);

        if ($job->attempts() > 10) {
            \Log::error('No account available for piggy bank "' . $payload['data']['name'] . '". KILL!');

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }



        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->overruleUser($user);

        /*
         * Maybe the piggy bank has already been imported
         */
        $importEntry = $repository->findImportEntry($importMap, 'Piggybank', intval($payload['data']['id']));

        /*
         * if so, delete job and return:
         */
        if (!is_null($importEntry)) {
            \Log::debug('Already imported piggy bank ' . $payload['data']['name']);

            $importMap->jobsdone++;
            $importMap->save();

            $job->delete(); // count fixed
            return;
        }

        /*
         * Try to find related piggybank:
         */
        $piggyBank = $this->findByName($payload['data']['name']);

        /*
         * Find an account (any account, really, at this point).
         */
        $accountType = $accounts->findAccountType('Asset account');

        /** @var Collection $set */
        $set = $accounts->getByAccountType($accountType);

        /*
         * If there is an account to attach to this piggy bank, simply use that one.
         */
        if ($set->count() > 0) {
            /** @var \Account $account */
            $account                       = $set->first();
            $payload['data']['account_id'] = $account->id;
        } else {
            \Log::notice('No account available yet for piggy bank "' . $payload['data']['name'] . '".');
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
         * No existing piggy bank, create it:
         */
        if (is_null($piggyBank)) {
            $payload['data']['targetamount']  = floatval($payload['data']['target']);
            $payload['data']['repeats']       = 0;
            $payload['data']['rep_every']     = 1;
            $payload['data']['reminder_skip'] = 1;
            $payload['data']['rep_times']     = 1;
            $piggyBank = $this->store($payload['data']);
            /*
             * Store and fire event.
             */
            $repository->store($importMap, 'Piggybank', intval($payload['data']['id']), $piggyBank->id);
            \Log::debug('Imported piggy "' . $payload['data']['name'] . '".');
            \Event::fire('piggybanks.store', [$piggyBank]);
        } else {
            /*
             * Already have a piggy bank with this name, we skip it.
             */
            $this->_repository->store($importMap, 'Piggybank', $payload['data']['id'], $piggyBank->id);
            \Log::debug('Already imported piggy "' . $payload['data']['name'] . '".');
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

    public function findByName($piggyBankName)
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', $this->_user->id
        )->where('piggybanks.name', $piggyBankName)->first(['piggybanks.*']);
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', $this->_user->id
        )->count();
    }

    public function countNonrepeating()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', $this->_user->id
        )->where('repeats', 0)->count();

    }

    public function countRepeating()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', $this->_user->id
        )->where('repeats', 1)->count();
    }

    /**
     * @param \Piggybank $piggyBank
     *
     * @return mixed|void
     */
    public function destroy(\Piggybank $piggyBank)
    {
        $piggyBank->delete();

        return true;
    }

    /**
     * @param $piggyBankId
     *
     * @return mixed
     */
    public function find($piggyBankId)
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', $this->_user->id
        )->where('piggybanks.id', $piggyBankId)->first(['piggybanks.*']);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $piggies = $this->_user->piggybanks()->with(['account', 'piggybankrepetitions'])->get();

        foreach ($piggies as $pig) {
            $pig->leftInAccount = $this->leftOnAccount($pig->account);
        }

        return $piggies;
    }

    /**
     * @param \Account $account
     *
     * @return mixed|void
     */
    public function leftOnAccount(\Account $account)
    {
        $balance = $account->balance();
        /** @var \Piggybank $p */
        foreach ($account->piggybanks()->get() as $p) {
            $balance -= $p->currentRelevantRep()->currentamount;
        }

        return $balance;

    }

    /**
     * @param \Piggybank $piggyBank
     * @param            $amount
     *
     * @return bool|mixed
     */
    public function modifyAmount(\Piggybank $piggyBank, $amount)
    {
        $rep = $piggyBank->currentRelevantRep();
        if (!is_null($rep)) {
            $rep->currentamount += $amount;
            $rep->save();
        }


        return true;

    }

    /**
     * @param $data
     *
     * @return \Piggybank
     */
    public function store($data)
    {
        if (isset($data['targetdate']) && $data['targetdate'] == '') {
            unset($data['targetdate']);
        }
        if (isset($data['reminder']) && $data['reminder'] == 'none') {
            unset($data['reminder']);
        }
        if (isset($data['startdate']) && $data['startdate'] == '') {
            unset($data['startdate']);
        }

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->overruleUser($this->_user);
        $account = isset($data['account_id']) ? $accounts->find($data['account_id']) : null;


        $piggyBank = new \Piggybank($data);

        if (!is_null($piggyBank->reminder) && is_null($piggyBank->startdate) && is_null($piggyBank->targetdate)) {

            $piggyBank->errors()->add('reminder', 'Cannot create reminders without start ~ AND target date.');
            \Log::error('PiggyBank create-error: ' . $piggyBank->errors()->first());
            return $piggyBank;

        }


        if ($piggyBank->repeats && !isset($data['targetdate'])) {
            $piggyBank->errors()->add('targetdate', 'Target date is mandatory!');
            \Log::error('PiggyBank create-error: ' . $piggyBank->errors()->first());

            return $piggyBank;
        }
        if (!is_null($account)) {
            $piggyBank->account()->associate($account);
        }
        $today = new Carbon;

        if ($piggyBank->validate()) {
            if (!is_null($piggyBank->targetdate) && $piggyBank->targetdate < $today) {
                $piggyBank->errors()->add('targetdate', 'Target date cannot be in the past.');
                \Log::error('PiggyBank create-error: ' . $piggyBank->errors()->first());

                return $piggyBank;
            }

            if (!is_null($piggyBank->reminder) && !is_null($piggyBank->targetdate)) {
                // first period for reminder is AFTER target date.
                $reminderSkip  = $piggyBank->reminder_skip < 1 ? 1 : intval($piggyBank->reminder_skip);
                $firstReminder = new Carbon;
                switch ($piggyBank->reminder) {
                    case 'day':
                        $firstReminder->addDays($reminderSkip);
                        break;
                    case 'week':
                        $firstReminder->addWeeks($reminderSkip);
                        break;
                    case 'month':
                        $firstReminder->addMonths($reminderSkip);
                        break;
                    case 'year':
                        $firstReminder->addYears($reminderSkip);
                        break;
                    default:
                        throw new FireflyException('Invalid reminder period');
                        break;
                }
                if ($firstReminder > $piggyBank->targetdate) {
                    $piggyBank->errors()->add(
                        'reminder', 'The reminder has been set to remind you after the piggy bank will expire.'
                    );
                    \Log::error('PiggyBank create-error: ' . $piggyBank->errors()->first());

                    return $piggyBank;
                }
            }
            $piggyBank->save();
        }


        return $piggyBank;
    }

    /**
     * @param \Piggybank $piggy
     * @param            $data
     *
     * @return mixed
     */
    public function update(\Piggybank $piggy, $data)
    {
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->overruleUser($this->_user);
        $account = isset($data['account_id']) ? $accounts->find($data['account_id']) : null;

        if (!is_null($account)) {
            $piggy->account()->associate($account);
        }

        $piggy->name          = $data['name'];
        $piggy->targetamount  = floatval($data['targetamount']);
        $piggy->reminder      = isset($data['reminder']) && $data['reminder'] != 'none' ? $data['reminder'] : null;
        $piggy->reminder_skip = $data['reminder_skip'];
        $piggy->targetdate    = strlen($data['targetdate']) > 0 ? new Carbon($data['targetdate']) : null;
        $piggy->startdate
                              =
            isset($data['startdate']) && strlen($data['startdate']) > 0 ? new Carbon($data['startdate']) : null;


        foreach ($piggy->piggybankrepetitions()->get() as $rep) {
            $rep->delete();
        }

        if ($piggy->repeats == 1) {
            $piggy->rep_every  = intval($data['rep_every']);
            $piggy->rep_length = $data['rep_length'];
        }

        if ($piggy->validate()) {
            // check the things we check for new piggies
            $piggy->save();
        }


        return $piggy;

    }

}