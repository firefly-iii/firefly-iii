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
     * Get all accounts of the selected types. Is also capable of handling DataTables' parameters.
     *
     * @param array $types
     *
     * @return Collection
     */
    public function getAccountsByType(array $types);


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
