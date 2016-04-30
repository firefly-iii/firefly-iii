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
use FireflyIII\Crud\Split\JournalInterface;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SplitJournalFormRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Log;
use Session;

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
        $journalData = session('temporary_split_data');
        $currencies    = ExpandedForm::makeSelectList($currencyRepository->get());
        $assetAccounts = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));
        $budgets       = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        if (!is_array($journalData)) {
            throw new FireflyException('Could not find transaction data in your session. Please go back and try again.'); // translate me.
        }

        Log::debug('Journal data', $journalData);


        return view('split.journals.from-store', compact('currencies', 'assetAccounts', 'budgets'))->with('data', $journalData);


    }

    /**
     * @param SplitJournalFormRequest $request
     * @param JournalInterface        $repository
     *
     * @return mixed
     */
    public function postJournalFromStore(SplitJournalFormRequest $request, JournalInterface $repository)
    {
        $data = $request->getSplitData();

        // store an empty journal first. This will be the place holder.
        $journal = $repository->storeJournal($data);
        // Then, store each transaction individually.

        foreach ($data['transactions'] as $transaction) {
            $transactions = $repository->storeTransaction($journal, $transaction);
        }

        // TODO move to repository.
        $journal->completed = true;
        $journal->save();

        // forget temp journal data
        Session::forget('temporary_split_data');

        // this is where we originally came from.
        return redirect(session('transactions.create.url'));
    }

}