<?php


namespace Firefly\Storage\Account;

use Carbon\Carbon;

/**
 * Interface AccountRepositoryInterface
 *
 * @package Firefly\Storage\Account
 */
interface AccountRepositoryInterface
{

    /**
     * @return mixed
     */
    public function count();

    /**
     * @return mixed
     */
    public function get();

    /**
     * @return mixed
     */
    public function getBeneficiaries();

    /**
     * @param $accountId
     *
     * @return mixed
     */
    public function find($accountId);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function findByName($name);

    /**
     * @return mixed
     */
    public function getCashAccount();

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getByIds($ids);

    /**
     * @return mixed
     */
    public function getDefault();

    /**
     * @return mixed
     */
    public function getActiveDefault();

    /**
     * @return mixed
     */
    public function getActiveDefaultAsSelectList();

    /**
     * @param $data
     *
     * @return mixed
     */
    public function store($data);

    /**
     * @param        $data
     * @param Carbon $date
     * @param int    $amount
     *
     * @return mixed
     */
    public function storeWithInitialBalance($data, Carbon $date, $amount = 0);

    /**
     * @param $name
     *
     * @return mixed
     */
    public function createOrFindBeneficiary($name);

    /**
     * @param              $name
     * @param \AccountType $type
     *
     * @return mixed
     */
    public function createOrFind($name, \AccountType $type);

} 