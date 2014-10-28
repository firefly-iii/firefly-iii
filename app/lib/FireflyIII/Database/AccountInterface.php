<?php

namespace FireflyIII\Database;

use Illuminate\Support\Collection;

/**
 * Interface AccountInterface
 *
 * @package FireflyIII\Database
 */
interface AccountInterface
{

    /**
     * Get all asset accounts. The parameters are optional and are provided by the DataTables plugin.
     *
     * @param array $parameters
     *
     * @return Collection
     */
    public function getAssetAccounts(array $parameters = []);

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
     * @param array $parameters
     *
     * @return Collection
     */

    /**
     * @param \Account $account
     *
     * @return \Account|null
     */
    public function findInitialBalanceAccount(\Account $account);

    public function getExpenseAccounts(array $parameters = []);

    /**
     * Get all revenue accounts.
     *
     * @param array $parameters
     *
     * @return Collection
     */
    public function getRevenueAccounts(array $parameters = []);

    /**
     * Get all accounts of the selected types. Is also capable of handling DataTables' parameters.
     *
     * @param array $types
     * @param array $parameters
     *
     * @return Collection
     */
    public function getAccountsByType(array $types, array $parameters = []);

    /**
     * Counts the number of accounts found with the included types.
     *
     * @param array $types
     *
     * @return int
     */
    public function countAccountsByType(array $types);

    /**
     * Get all default accounts.
     *
     * @return Collection
     */
    public function getDefaultAccounts();

    /**
     * @param \Account $account
     *
     * @return \TransactionJournal|null
     */
    public function openingBalanceTransaction(\Account $account);

    /**
     * @param \Account $account
     * @param array $data
     *
     * @return bool
     */
    public function storeInitialBalance(\Account $account, array $data);
} 