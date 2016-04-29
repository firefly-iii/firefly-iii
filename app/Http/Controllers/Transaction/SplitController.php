<?php
/**
 * SplitController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers\Transaction;


use ExpandedForm;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;

/**
 * Class SplitController
 *
 * @package FireflyIII\Http\Controllers\Transaction
 */
class SplitController extends Controller
{
    /**
     *
     */
    public function journalFromStore()
    {
        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        // expect data to be in session or in post?
        $journalData   = session('temporary_split_data');
        $currency      = $currencyRepository->find(intval($journalData['amount_currency_id_amount']));
        $assetAccounts = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));
        $budgets       = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        if (!is_array($journalData)) {
            throw new FireflyException('Could not find transaction data in your session. Please go back and try again.'); // translate me.
        }
        //        echo '<pre>';
        //        var_dump($journalData);
        //        echo '</pre>';
        //        exit;

        return view('split.journals.from-store', compact('currency', 'assetAccounts', 'budgets'))->with('data', $journalData);


    }

    public function postJournalFromStore()
    {
        // forget temp journal data
        // Session::forget('temporary_split_data');
    }

}