<?php


namespace Firefly\Storage\Account;

class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function __construct()
    {
    }

    public function count() {
        return \Auth::user()->accounts()->count();

    }

}