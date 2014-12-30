<?php

namespace FireflyIII\Helper\TransactionJournal;

use Illuminate\Support\Collection;

/**
 * Interface HelperInterface
 *
 * @package FireflyIII\Helper\TransactionJournal
 */
interface HelperInterface
{
    /**
     *
     * Get the account_id, which is the asset account that paid for the transaction.
     *
     * @param string     $what
     * @param Collection $transactions
     *
     * @return int
     */
    public function getAssetAccount($what, Collection $transactions);

    /**
     * @return Collection
     */
    public function getAssetAccounts();

    /**
     * @return Collection
     */
    public function getBudgets();

    /**
     * @return Collection
     */
    public function getPiggyBanks();

    /**
     * @return Collection
     */
    public function getRepeatedExpenses();

}