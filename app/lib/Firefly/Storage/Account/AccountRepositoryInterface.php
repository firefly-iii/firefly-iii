<?php


namespace Firefly\Storage\Account;

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
     * Gets a list of accounts that have the mentioned type. Will automatically convert
     * strings in this array to actual (model) account types.
     *
     * @param array $types
     *
     * @return Collection
     */
    public function getOfTypes(array $types);

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function firstOrCreate(array $data);

    /**
     * @param \Account $account
     *
     * @return mixed
     */
    public function destroy(\Account $account);

    /**
     * @param $accountId
     *
     * @return mixed
     */
    public function find($accountId);

    /**
     * @param              $name
     * @param \AccountType $type
     *
     * @return \Account
     */
    public function findByNameAndAccountType($name, \AccountType $type);

    /**
     * @param $type
     * @return mixed
     */
    public function findAccountType($type);

    /**
     * @return mixed
     */
    public function get();

    /**
     * @return mixed
     */
    public function getActiveDefault();

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getByIds(array $ids);

    /**
     * @return mixed
     */
    public function getCashAccount();

    /**
     * @return mixed
     */
    public function getDefault();

    /**
     * @param \AccountType $type
     * @return mixed
     */
    public function getByAccountType(\AccountType $type);

    /**
     * @param \User $user
     * @return mixed
     */
    public function overruleUser(\User $user);

    /**
     * @param $data
     *
     * @return \Account
     */
    public function store($data);

    /**
     * @param \Account $account
     * @param          $data
     *
     * @return mixed
     */
    public function update(\Account $account, $data);

} 