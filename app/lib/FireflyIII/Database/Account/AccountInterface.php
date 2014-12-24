<?php

namespace FireflyIII\Database\Account;

use Illuminate\Support\Collection;

/**
 * Interface AccountInterface
 *
 * @package FireflyIII\Database
 */
interface AccountInterface
{

    /**
     * Counts the number of accounts found with the included types.
     *
     * @param array $types
     *
     * @return int
     */
    public function countAccountsByType(array $types);

    /**
     * Counts the number of total asset accounts. Useful for DataTables.
     *
     * @return int
     */
    public function countAssetAccounts();

    /**
     * Counts the number of total expense accounts. Useful for DataTables.
     *
     * @return int
     */
    public function countExpenseAccounts();

    /**
     * Counts the number of total revenue accounts. Useful for DataTables.
     *
     * @return int
     */
    public function countRevenueAccounts();

    /**
     * @param \Account $account
     *
     * @return \Account|null
     */
    public function findInitialBalanceAccount(\Account $account);

    /**
     * Get all accounts of the selected types. Is also capable of handling DataTables' parameters.
     *
     * @param array $types
     *
     * @return Collection
     */
    public function getAccountsByType(array $types);

    /**
     * Get all asset accounts. The parameters are optional and are provided by the DataTables plugin.
     *
     * @return Collection
     */
    public function getAssetAccounts();

    /**
     * @return Collection
     */
    public function getExpenseAccounts();

    /**
     * Get all revenue accounts.
     *
     * @return Collection
     */
    public function getRevenueAccounts();

    /**
     * @param \Account $account
     *
     * @return \TransactionJournal|null
     */
    public function openingBalanceTransaction(\Account $account);

    /**
     * @param \Account $account
     * @param array    $data
     *
     * @return bool
     */
    public function storeInitialBalance(\Account $account, array $data);
} 