<?php

namespace Firefly\Storage\Piggybank;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;


/**
 * Class EloquentLimitRepository
 *
 * @package Firefly\Storage\Limit
 */
class EloquentPiggybankRepository implements PiggybankRepositoryInterface
{


    /**
     * @return mixed
     */
    public function count()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->count();
    }

    public function countNonrepeating()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->where('repeats', 0)->count();

    }

    public function countRepeating()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
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
            'accounts.user_id', \Auth::user()->id
        )->where('piggybanks.id', $piggyBankId)->first(['piggybanks.*']);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return \Auth::user()->piggybanks()->with(['account', 'piggybankrepetitions'])->get();
    }

    /**
     * @param $data
     *
     * @return \Piggybank
     */
    public function store($data)
    {
        if ($data['targetdate'] == '') {
            unset($data['targetdate']);
        }
        if ($data['reminder'] == 'none') {
            unset($data['reminder']);
        }

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $account = isset($data['account_id']) ? $accounts->find($data['account_id']) : null;


        $piggyBank = new \Piggybank($data);
        if (!is_null($account)) {
            $piggyBank->account()->associate($account);
        }
        $today = new Carbon;

        if ($piggyBank->validate()) {
            if (!is_null($piggyBank->targetdate) && $piggyBank->targetdate < $today) {
                $piggyBank->errors()->add('targetdate', 'Target date cannot be in the past.');

                return $piggyBank;
            }

            if (!is_null($piggyBank->reminder) && !is_null($piggyBank->targetdate)) {
                // first period for reminder is AFTER target date.
                $reminderSkip = $piggyBank->reminder_skip < 1 ? 1 : intval($piggyBank->reminder_skip);
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
        $account = isset($data['account_id']) ? $accounts->find($data['account_id']) : null;

        if (!is_null($account)) {
            $piggy->account()->associate($account);
        }

        $piggy->name = $data['name'];
        $piggy->targetamount = floatval($data['targetamount']);
        $piggy->reminder = isset($data['reminder']) && $data['reminder'] != 'none' ? $data['reminder'] : null;
        $piggy->reminder_skip = $data['reminder_skip'];
        $piggy->targetdate = strlen($data['targetdate']) > 0 ? new Carbon($data['targetdate']) : null;
        $piggy->startdate
            = isset($data['startdate']) && strlen($data['startdate']) > 0 ? new Carbon($data['startdate']) : null;

        // everything we can update for NON repeating piggy banks:
        if ($piggy->repeats == 0) {
            // if non-repeating there is only one PiggyBank instance and we can delete it safely.
            // it will be recreated.
            $piggy->piggybankrepetitions()->first()->delete();
        } else {
            // we can delete all of them, because reasons
            foreach ($piggy->piggybankrepetitions()->get() as $rep) {
                $rep->delete();
            }

            $piggy->rep_every = intval($data['rep_every']);
            $piggy->rep_length = $data['rep_length'];
        }
        if ($piggy->validate()) {
            // check the things we check for new piggies
            $piggy->save();
        }


        return $piggy;

    }

    /**
     * @param \Piggybank $piggyBank
     * @param            $amount
     *
     * @return mixed|void
     */
    public function updateAmount(\Piggybank $piggyBank, $amount)
    {
        $piggyBank->amount = floatval($amount);
        if ($piggyBank->validate()) {
            $piggyBank->save();
        }

    }
}