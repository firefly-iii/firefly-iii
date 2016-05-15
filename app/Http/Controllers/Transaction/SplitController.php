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
use FireflyIII\Events\TransactionJournalUpdated;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SplitJournalFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Http\Request;
use Log;
use Preferences;
use Session;
use Steam;
use URL;
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
     * @param TransactionJournal $journal
     *
     * @return View
     */
    public function create(TransactionJournal $journal)
    {
        $accountRepository  = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $currencyRepository = app('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $budgetRepository   = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $assetAccounts      = ExpandedForm::makeSelectList($accountRepository->getAccountsByType(['Default account', 'Asset account']));
        $sessionData        = session('journal-data', []);
        $uploadSize         = min(Steam::phpBytes(ini_get('upload_max_filesize')), Steam::phpBytes(ini_get('post_max_size')));
        $currencies         = ExpandedForm::makeSelectList($currencyRepository->get());
        $budgets            = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        $preFilled          = [
            'what'                        => $sessionData['what'] ?? 'withdrawal',
            'journal_amount'              => $sessionData['amount'] ?? 0,
            'journal_source_account_id'   => $sessionData['source_account_id'] ?? 0,
            'journal_source_account_name' => $sessionData['source_account_name'] ?? '',
            'description'                 => [$journal->description],
            'destination_account_name'    => [$sessionData['destination_account_name']],
            'destination_account_id'      => [$sessionData['destination_account_id']],
            'amount'                      => [$sessionData['amount']],
            'budget_id'                   => [$sessionData['budget_id']],
            'category'                    => [$sessionData['category']],
        ];

        return view('split.journals.create', compact('journal', 'preFilled', 'assetAccounts', 'currencies', 'budgets', 'uploadSize'));
    }

    /**
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function edit(Request $request, TransactionJournal $journal)
    {
        $currencyRepository = app('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $accountRepository  = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $budgetRepository   = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        $uploadSize         = min(Steam::phpBytes(ini_get('upload_max_filesize')), Steam::phpBytes(ini_get('post_max_size')));
        $currencies         = ExpandedForm::makeSelectList($currencyRepository->get());
        $assetAccounts      = ExpandedForm::makeSelectList($accountRepository->getAccountsByType(['Default account', 'Asset account']));
        $budgets            = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        $preFilled          = $this->arrayFromJournal($request, $journal);

        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-split-' . $preFilled['what']);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('transactions.edit-split.fromUpdate') !== true) {
            Session::put('transactions.edit-split.url', URL::previous());
        }
        Session::forget('transactions.edit-split.fromUpdate');

        return view(
            'split.journals.edit',
            compact('currencies', 'preFilled', 'amount', 'sourceAccounts', 'uploadSize', 'destinationAccounts', 'assetAccounts', 'budgets', 'journal')
        );
    }

    /**
     * @param JournalInterface        $repository
     * @param SplitJournalFormRequest $request
     * @param TransactionJournal      $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(JournalInterface $repository, SplitJournalFormRequest $request, TransactionJournal $journal)
    {
        $data = $request->getSplitData();

        foreach ($data['transactions'] as $transaction) {
            $repository->storeTransaction($journal, $transaction);
        }

        // TODO move to repository
        $journal->completed = 1;
        $journal->save();

        Session::flash('success', strval(trans('firefly.stored_journal', ['description' => e($journal->description)])));
        Preferences::mark();

        if (intval($request->get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('transactions.create.fromStore', true);

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('transactions.create.url'));
    }

    /**
     * @param TransactionJournal        $journal
     * @param SplitJournalFormRequest   $request
     * @param JournalInterface          $repository
     * @param AttachmentHelperInterface $att
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(TransactionJournal $journal, SplitJournalFormRequest $request, JournalInterface $repository, AttachmentHelperInterface $att)
    {

        $data    = $request->getSplitData();
        $journal = $repository->updateJournal($journal, $data);

        // save attachments:
        $att->saveAttachmentsForModel($journal);

        event(new TransactionJournalUpdated($journal));
        // update, get events by date and sort DESC

        // flash messages
        if (count($att->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $att->getMessages()->get('attachments'));
        }


        $type = strtolower($journal->transaction_type_type ?? TransactionJournal::transactionTypeStr($journal));
        Session::flash('success', strval(trans('firefly.updated_' . $type, ['description' => e($data['journal_description'])])));
        Preferences::mark();

        if (intval($request->get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit-split.fromUpdate', true);

            return redirect(route('split.journal.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('transactions.edit-split.url'));

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
        $sourceAccounts      = TransactionJournal::sourceAccountList($journal);
        $destinationAccounts = TransactionJournal::destinationAccountList($journal);
        $array               = [
            'journal_description'            => $request->old('journal_description', $journal->description),
            'journal_amount'                 => TransactionJournal::amountPositive($journal),
            'sourceAccounts'                 => $sourceAccounts,
            'journal_source_account_id'      => $sourceAccounts->first()->id,
            'journal_source_account_name'    => $sourceAccounts->first()->name,
            'journal_destination_account_id' => $destinationAccounts->first()->id,
            'transaction_currency_id'        => $request->old('transaction_currency_id', $journal->transaction_currency_id),
            'destinationAccounts'            => $destinationAccounts,
            'what'                           => strtolower(TransactionJournal::transactionTypeStr($journal)),
            'date'                           => $request->old('date', $journal->date),
            'interest_date'                  => $request->old('interest_date', $journal->interest_date),
            'book_date'                      => $request->old('book_date', $journal->book_date),
            'process_date'                   => $request->old('process_date', $journal->process_date),
            'description'                    => [],
            'source_account_id'              => [],
            'source_account_name'            => [],
            'destination_account_id'         => [],
            'destination_account_name'       => [],
            'amount'                         => [],
            'budget_id'                      => [],
            'category'                       => [],
        ];
        $index               = 0;
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            $budget       = $transaction->budgets()->first();
            $category     = $transaction->categories()->first();
            $budgetId     = 0;
            $categoryName = '';
            if (!is_null($budget)) {
                $budgetId = $budget->id;
            }

            if (!is_null($category)) {
                $categoryName = $category->name;
            }

            $budgetId        = $request->old('budget_id')[$index] ?? $budgetId;
            $categoryName    = $request->old('category')[$index] ?? $categoryName;
            $amount          = $request->old('amount')[$index] ?? $transaction->amount;
            $description     = $request->old('description')[$index] ?? $transaction->description;
            $destinationName = $request->old('destination_account_name')[$index] ?? $transaction->account->name;

            // any transfer not from the source:
            if (($journal->isWithdrawal() || $journal->isDeposit()) && $transaction->account_id !== $sourceAccounts->first()->id) {
                $array['description'][]              = $description;
                $array['destination_account_id'][]   = $transaction->account_id;
                $array['destination_account_name'][] = $destinationName;
                $array['amount'][]                   = $amount;
                $array['budget_id'][]                = intval($budgetId);
                $array['category'][]                 = $categoryName;
                // only add one when "valid" transaction
                $index++;
            }
        }


        return $array;
    }

}