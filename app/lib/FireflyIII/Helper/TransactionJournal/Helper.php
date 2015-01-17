<?php

namespace FireflyIII\Helper\TransactionJournal;

use Illuminate\Support\Collection;

/**
 * Class Helper
 *
 * @package FireflyIII\Helper\TransactionJournal
 */
class Helper implements HelperInterface
{

    /**
     *
     * Get the account_id, which is the asset account that paid for the transaction.
     *
     * @param string     $what
     * @param Collection $transactions
     *
     * @return mixed
     */
    public function getAssetAccount($what, Collection $transactions)
    {
        if ($what == 'withdrawal') {
            // transaction #1 is the one that paid for it.
            return intval($transactions[1]->account->id);
        }

        // otherwise (its a deposit), it's been paid into account #0.
        return intval($transactions[0]->account->id);
    }

    /**
     * @return Collection
     */
    public function getAssetAccounts()
    {
        /** @var \FireflyIII\Database\Account\Account $accountRepository */
        $accountRepository = \App::make('FireflyIII\Database\Account\Account');

        return $accountRepository->getAccountsByType(['Default account', 'Asset account']);
    }

    /**
     * @return Collection
     */
    public function getBudgets()
    {
        /** @var \FireflyIII\Database\Budget\Budget $budgetRepository */
        $budgetRepository = \App::make('FireflyIII\Database\Budget\Budget');

        return $budgetRepository->get();

    }

    /**
     * @return Collection
     */
    public function getPiggyBanks()
    {
        /** @var \FireflyIII\Database\PiggyBank\PiggyBank $piggyRepository */
        $piggyRepository = \App::make('FireflyIII\Database\PiggyBank\PiggyBank');

        return $piggyRepository->get();


    }

    /**
     * @return Collection
     */
    public function getRepeatedExpenses()
    {
        /** @var \FireflyIII\Database\PiggyBank\RepeatedExpense $repRepository */
        $repRepository = \App::make('FireflyIII\Database\PiggyBank\RepeatedExpense');

        return $repRepository->get();


    }


}
