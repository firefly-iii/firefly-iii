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
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\Request;
use Log;
use Session;
use View;

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
    public function __construct()
    {
        parent::__construct();
        View::share('mainTitleIcon', 'fa-share-alt');
        View::share('title', trans('firefly.split-transactions'));
    }

    /**
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function edit(Request $request, TransactionJournal $journal)
    {
        $count = $journal->transactions()->count();
        if ($count === 2) {
            return redirect(route('transactions.edit', [$journal->id]));
        }

        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        $currencies    = ExpandedForm::makeSelectList($currencyRepository->get());
        $assetAccounts = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));
        $budgets       = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        $preFilled     = $this->arrayFromJournal($request, $journal);

        // get the transactions:

        return view(
            'split.journals.edit',
            compact('currencies', 'preFilled', 'amount', 'sourceAccounts', 'destinationAccounts', 'assetAccounts', 'budgets', 'what', 'journal')
        );
    }

    /**
     * @param Request $request
     *
     * @return mixed
     * @throws FireflyException
     */
    public function journalFromStore(Request $request)
    {
        if ($request->old('journal_currency_id')) {
            $preFilled = $this->arrayFromOldData($request->old());
        } else {
            $preFilled = $this->arrayFromSession();
        }

        Session::flash('preFilled', $preFilled);
        View::share('subTitle', trans('firefly.split-new-transaction'));

        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        $currencies    = ExpandedForm::makeSelectList($currencyRepository->get());
        $assetAccounts = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));
        $budgets       = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());


        return view('split.journals.from-store', compact('currencies', 'assetAccounts', 'budgets', 'preFilled'));


    }

    /**
     * @param SplitJournalFormRequest $request
     * @param JournalInterface        $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    public function postJournalFromStore(SplitJournalFormRequest $request, JournalInterface $repository)
    {
        $data = $request->getSplitData();

        // store an empty journal first. This will be the place holder.
        $journal = $repository->storeJournal($data);
        // Then, store each transaction individually.

        if (is_null($journal->id)) {
            throw new FireflyException('Could not store transaction.');
        }
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

    /**
     * @param SplitJournalFormRequest $request
     * @param JournalInterface        $repository
     */
    public function update(SplitJournalFormRequest $request, JournalInterface $repository)
    {
        echo 'ok';
    }

    /**
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return array
     */
    private function arrayFromJournal(Request $request, TransactionJournal $journal): array
    {
        if (Session::has('_old_input')) {
            Log::debug('Old input: ', session('_old_input'));
        }
        $sourceAccounts = TransactionJournal::sourceAccountList($journal);
        $firstSourceId  = $sourceAccounts->first()->id;
        $array          = [
            'journal_description'      => $request->old('journal_description', $journal->description),
            'journal_amount'           => TransactionJournal::amountPositive($journal),
            'sourceAccounts'           => $sourceAccounts,
            'transaction_currency_id'  => $request->old('transaction_currency_id', $journal->transaction_currency_id),
            'destinationAccounts'      => TransactionJournal::destinationAccountList($journal),
            'what'                     => strtolower(TransactionJournal::transactionTypeStr($journal)),
            'date'                     => $request->old('date', $journal->date),
            'interest_date'            => $request->old('interest_date', $journal->interest_date),
            'book_date'                => $request->old('book_date', $journal->book_date),
            'process_date'             => $request->old('process_date', $journal->process_date),
            'description'              => [],
            'destination_account_id'   => [],
            'destination_account_name' => [],
            'amount'                   => [],
            'budget_id'                => [],
            'category'                 => [],
        ];
        $index = 0;
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            //if ($journal->isWithdrawal() && $transaction->account_id !== $firstSourceId) {
                $array['description'][]              = $transaction->description;
                $array['destination_account_id'][]   = $transaction->account_id;
                $array['destination_account_name'][] = $transaction->account->name;
                $array['amount'][]                   = $transaction->amount;
            //}
            $budget   = $transaction->budgets()->first();
            $budgetId = 0;
            if (!is_null($budget)) {
                $budgetId = $budget->id;
            }

            $category     = $transaction->categories()->first();
            $categoryName = '';
            if (!is_null($category)) {
                $categoryName = $category->name;
            }
            $array['budget_id'][] = $budgetId;
            $array['category'][]  = $categoryName;
        }

        return $array;
    }

    /**
     * @param array $old
     *
     * @return array
     */
    private function arrayFromOldData(array $old): array
    {
        // this array is pretty much equal to what we expect it to be.
        Log::debug('Prefilled', $old);

        return $old;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    private function arrayFromSession(): array
    {
        // expect data to be in session or in post?
        $data = session('temporary_split_data');

        if (!is_array($data)) {
            Log::error('Could not find transaction data in your session. Please go back and try again.', ['data' => $data]); // translate me.
            throw new FireflyException('Could not find transaction data in your session. Please go back and try again.'); // translate me.
        }

        Log::debug('Journal data', $data);

        $preFilled = [
            'what'                             => $data['what'],
            'journal_description'              => $data['description'],
            'journal_source_account_id'        => $data['source_account_id'],
            'journal_source_account_name'      => $data['source_account_name'],
            'journal_destination_account_id'   => $data['destination_account_id'],
            'journal_destination_account_name' => $data['destination_account_name'],
            'journal_amount'                   => $data['amount'],
            'journal_currency_id'              => $data['amount_currency_id_amount'],
            'date'                             => $data['date'],
            'interest_date'                    => $data['interest_date'],
            'book_date'                        => $data['book_date'],
            'process_date'                     => $data['process_date'],

            'description'              => [],
            'destination_account_id'   => [],
            'destination_account_name' => [],
            'amount'                   => [],
            'budget_id'                => [],
            'category'                 => [],
        ];

        // create the first transaction:
        $preFilled['description'][]              = $data['description'];
        $preFilled['destination_account_id'][]   = $data['destination_account_id'];
        $preFilled['destination_account_name'][] = $data['destination_account_name'];
        $preFilled['amount'][]                   = $data['amount'];
        $preFilled['budget_id'][]                = $data['budget_id'];
        $preFilled['category'][]                 = $data['category'];

        //        echo '<pre>';
        //        var_dump($data);
        //        var_dump($preFilled);
        //        exit;
        Log::debug('Prefilled', $preFilled);

        return $preFilled;
    }

}