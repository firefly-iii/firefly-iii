<?php

namespace Firefly\Storage\Piggybank;


/**
 * Class EloquentLimitRepository
 *
 * @package Firefly\Storage\Limit
 */
class EloquentPiggybankRepository implements PiggybankRepositoryInterface
{


    public function count()
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->count();
    }

    public function find($piggyBankId)
    {
        return \Piggybank::leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->where('piggybanks.id', $piggyBankId)->first('piggybanks.*');
    }

    public function get()
    {
        return \Piggybank::with('account')->leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')->where(
            'accounts.user_id', \Auth::user()->id
        )->get(['piggybanks.*']);
    }

    public function store($data)
    {
        var_dump($data);
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $account = isset($data['account_id']) ? $accounts->find($data['account_id']) : null;


        $piggyBank = new \Piggybank;
        $piggyBank->account()->associate($account);
        $piggyBank->targetdate
            = isset($data['targetdate']) && strlen($data['targetdate']) > 0 ? $data['targetdate'] : null;
        $piggyBank->name = isset($data['name']) ? $data['name'] : null;
        $piggyBank->amount = 0;
        $piggyBank->target = floatval($data['target']);
        $piggyBank->order = 1;
        if ($piggyBank->validate()) {
            $piggyBank->save();
        }

        return $piggyBank;
    }
}