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
        return \Piggybank::with('account')->leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->get(['piggybanks.*']);
    }

    /**
     * @param $data
     *
     * @return \Piggybank
     */
    public function store($data)
    {
        var_dump($data);
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
        $piggyBank->account()->associate($account);
        $today = new Carbon;

        if ($piggyBank->validate()) {
            echo 'Valid, but some more checking!';

            if (!is_null($piggyBank->targetdate) && $piggyBank->targetdate < $today) {
                $piggyBank->errors()->add('targetdate', 'Target date cannot be in the past.');

                return $piggyBank;
            }

            if (!is_null($piggyBank->reminder) && !is_null($piggyBank->targetdate)) {
                // first period for reminder is AFTER target date.
                // just flash a warning
                $reminderSkip = $piggyBank->reminder_skip < 1 ? 1 : intval($piggyBank->reminder_skip);
                $firstReminder = new Carbon;
                switch($piggyBank->reminder) {
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
                if($firstReminder > $piggyBank->targetdate) {
                    $piggyBank->errors()->add('reminder','Something reminder bla.');
                    return $piggyBank;
                }
            }

            $piggyBank->save();
        } else {
            echo 'Does not validate';

            print_r($piggyBank->errors()->all());
            exit;
        }

        return $piggyBank;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function update($data)
    {
        $piggyBank = $this->find($data['id']);
        if ($piggyBank) {
            $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
            $account = $accounts->find($data['account_id']);
            // update piggybank accordingly:
            $piggyBank->name = $data['name'];
            $piggyBank->target = floatval($data['target']);
            $piggyBank->account()->associate($account);
            if ($piggyBank->validate()) {
                $piggyBank->save();
            }
        }

        return $piggyBank;
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