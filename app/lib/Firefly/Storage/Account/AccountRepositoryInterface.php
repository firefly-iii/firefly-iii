<?php


namespace Firefly\Storage\Account;


interface AccountRepositoryInterface
{

    public function count();

    public function store($data);
    public function storeWithInitialBalance($data,\Carbon\Carbon $date, $amount = 0);

} 