<?php


namespace Firefly\Storage\Account;

class EloquentAccountRepository implements AccountRepositoryInterface
{
    public $validator;

    public function __construct()
    {
    }

    public function count() {
        return \Auth::user()->accounts()->count();

    }

    public function store() {


        $default = \AccountType::where('description','Default account')->first();
        $balanceAT = \AccountType::where('description','Initial balance account')->first();

        $account = new \Account;
        $account->active = true;

        $account->user()->associate(\Auth::user());
        $account->name = \Input::get('name');
        $account->accountType()->associate($default);

        if(!$account->isValid()) {
            \Log::error('Could not create account: ' . $account->validator->messages()->first());
            $this->validator = $account->validator;
            return false;
        }

        $account->save();

        $balance = floatval(\Input::get('openingbalance'));
        if($balance != 0.00) {
            // create account
            $initial = new \Account;
            $account->active = false;

            $account->user()->associate(\Auth::user());
            $account->name = \Input::get('name').' initial balance';
            $account->accountType()->associate($balanceAT);
            $account->save();

            // create journal (access helper!)


            // create journal

            // create transaction

            // create
        }

    }

}