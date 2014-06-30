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
        $account = new \Account;
        $account->name = Input::get('name');

        if($account->isValid()) {
            
        }
        $this->validator = $account->validator;
        return false;
    }

}