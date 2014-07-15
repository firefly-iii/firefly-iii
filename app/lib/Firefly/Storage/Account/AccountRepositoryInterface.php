<?php


namespace Firefly\Storage\Account;


interface AccountRepositoryInterface
{

    public function count();

    public function get();

    public function getBeneficiaries();

    public function find($id);

    public function getByIds($ids);

    public function getDefault();

    public function getActiveDefault();

    public function getActiveDefaultAsSelectList();

    public function store($data);

    public function storeWithInitialBalance($data, \Carbon\Carbon $date, $amount = 0);

} 