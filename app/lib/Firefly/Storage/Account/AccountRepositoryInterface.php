<?php


namespace Firefly\Storage\Account;


interface AccountRepositoryInterface
{

    public function count();

    public function get();

    public function getBeneficiaries();

    public function find($id);

    public function findByName($name);

    public function getByIds($ids);

    public function getDefault();

    public function getActiveDefault();

    public function getActiveDefaultAsSelectList();

    public function store($data);

    public function storeWithInitialBalance($data, \Carbon\Carbon $date, $amount = 0);

    public function createOrFindBeneficiary($name);

    public function createOrFind($name, \AccountType $type);

} 