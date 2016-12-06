<?php
/**
 * SingleController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Transaction;


use ExpandedForm;
use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Log;
use Preferences;
use Session;
use Steam;
use URL;
use View;

/**
 * Class SingleController
 *
 * @package FireflyIII\Http\Controllers\Transaction
 */
class SingleController extends Controller
{
    /** @var  AccountRepositoryInterface */
    private $accounts;

    /** @var AttachmentHelperInterface */
    private $attachments;

    /** @var  BudgetRepositoryInterface */
    private $budgets;

    /** @var  PiggyBankRepositoryInterface */
    private $piggyBanks;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $maxFileSize = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        View::share('uploadSize', $uploadSize);

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->accounts    = app(AccountRepositoryInterface::class);
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->piggyBanks  = app(PiggyBankRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                View::share('title', trans('firefly.transactions'));
                View::share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );

    }

    /**
     * @param string $what
     *
     * @return View
     */
    public function create(string $what = TransactionType::DEPOSIT)
    {
        $what           = strtolower($what);
        $uploadSize     = min(Steam::phpBytes(ini_get('upload_max_filesize')), Steam::phpBytes(ini_get('post_max_size')));
        $assetAccounts  = ExpandedForm::makeSelectList($this->accounts->getActiveAccountsByType([AccountType::DEFAULT, AccountType::ASSET]));
        $budgets        = ExpandedForm::makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $piggyBanks     = $this->piggyBanks->getPiggyBanksWithAmount();
        $piggies        = ExpandedForm::makeSelectListWithEmpty($piggyBanks);
        $preFilled      = Session::has('preFilled') ? session('preFilled') : [];
        $subTitle       = trans('form.add_new_' . $what);
        $subTitleIcon   = 'fa-plus';
        $optionalFields = Preferences::get('transaction_journal_optional_fields', [])->data;

        Session::put('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (session('transactions.create.fromStore') !== true) {
            $url = URL::previous();
            Session::put('transactions.create.url', $url);
        }
        Session::forget('transactions.create.fromStore');
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'create-' . $what);

        asort($piggies);

        return view('transactions.create', compact('assetAccounts', 'subTitleIcon', 'uploadSize', 'budgets', 'what', 'piggies', 'subTitle', 'optionalFields'));
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionJournal $journal
     *
     * @return View
     */
    public function delete(TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }

        $what     = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle = trans('firefly.delete_' . $what, ['description' => $journal->description]);

        // put previous url in session
        Session::put('transactions.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'delete-' . $what);

        return view('transactions.delete', compact('journal', 'subTitle', 'what'));


    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param TransactionJournal         $transactionJournal
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(JournalRepositoryInterface $repository, TransactionJournal $transactionJournal)
    {
        if ($this->isOpeningBalance($transactionJournal)) {
            return $this->redirectToAccount($transactionJournal);
        }

        $type = TransactionJournal::transactionTypeStr($transactionJournal);
        Session::flash('success', strval(trans('firefly.deleted_' . strtolower($type), ['description' => e($transactionJournal->description)])));

        $repository->delete($transactionJournal);

        Preferences::mark();

        // redirect to previous URL:
        return redirect(session('transactions.delete.url'));
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return mixed
     */
    public function edit(TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }

        $count = $journal->transactions()->count();

        if ($count > 2) {
            return redirect(route('transactions.split.edit', [$journal->id]));
        }

        $what          = strtolower(TransactionJournal::transactionTypeStr($journal));
        $assetAccounts = ExpandedForm::makeSelectList($this->accounts->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]));
        $budgetList    = ExpandedForm::makeSelectListWithEmpty($this->budgets->getActiveBudgets());

        // view related code
        $subTitle = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);


        // journal related code
        $sourceAccounts      = TransactionJournal::sourceAccountList($journal);
        $destinationAccounts = TransactionJournal::destinationAccountList($journal);
        $optionalFields      = Preferences::get('transaction_journal_optional_fields', [])->data;
        $preFilled           = [
            'date'                     => TransactionJournal::dateAsString($journal),
            'interest_date'            => TransactionJournal::dateAsString($journal, 'interest_date'),
            'book_date'                => TransactionJournal::dateAsString($journal, 'book_date'),
            'process_date'             => TransactionJournal::dateAsString($journal, 'process_date'),
            'category'                 => TransactionJournal::categoryAsString($journal),
            'budget_id'                => TransactionJournal::budgetId($journal),
            'tags'                     => join(',', $journal->tags->pluck('tag')->toArray()),
            'source_account_id'        => $sourceAccounts->first()->id,
            'source_account_name'      => $sourceAccounts->first()->edit_name,
            'destination_account_id'   => $destinationAccounts->first()->id,
            'destination_account_name' => $destinationAccounts->first()->edit_name,
            'amount'                   => TransactionJournal::amountPositive($journal),

            // new custom fields:
            'due_date'                 => TransactionJournal::dateAsString($journal, 'due_date'),
            'payment_date'             => TransactionJournal::dateAsString($journal, 'payment_date'),
            'invoice_date'             => TransactionJournal::dateAsString($journal, 'invoice_date'),
            'interal_reference'        => $journal->getMeta('internal_reference'),
            'notes'                    => $journal->getMeta('notes'),
        ];

        if ($journal->isWithdrawal() && $destinationAccounts->first()->accountType->type == AccountType::CASH) {
            $preFilled['destination_account_name'] = '';
        }

        if ($journal->isDeposit() && $sourceAccounts->first()->accountType->type == AccountType::CASH) {
            $preFilled['source_account_name'] = '';
        }


        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-' . $what);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('transactions.edit.fromUpdate') !== true) {
            Session::put('transactions.edit.url', URL::previous());
        }
        Session::forget('transactions.edit.fromUpdate');

        return view(
            'transactions.edit',
            compact('journal', 'optionalFields', 'assetAccounts', 'what', 'budgetList', 'subTitle')
        )->with('data', $preFilled);
    }

    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(JournalFormRequest $request, JournalRepositoryInterface $repository)
    {
        $doSplit       = intval($request->get('split_journal')) === 1;
        $createAnother = intval($request->get('create_another')) === 1;
        $data          = $request->getJournalData();
        $journal       = $repository->store($data);
        if (is_null($journal->id)) {
            // error!
            Log::error('Could not store transaction journal: ', $journal->getErrors()->toArray());
            Session::flash('error', $journal->getErrors()->first());

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        $this->attachments->saveAttachmentsForModel($journal);

        // store the journal only, flash the rest.
        if (count($this->attachments->getErrors()->get('attachments')) > 0) {
            Session::flash('error', $this->attachments->getErrors()->get('attachments'));
        }
        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        event(new StoredTransactionJournal($journal, $data['piggy_bank_id']));

        Session::flash('success', strval(trans('firefly.stored_journal', ['description' => e($journal->description)])));
        Preferences::mark();

        if ($createAnother === true) {
            // set value so create routine will not overwrite URL:
            Session::put('transactions.create.fromStore', true);

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        if ($doSplit === true) {
            // redirect to edit screen:
            return redirect(route('transactions.split.edit', [$journal->id]));
        }


        // redirect to previous URL.
        return redirect(session('transactions.create.url'));

    }

    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(JournalFormRequest $request, JournalRepositoryInterface $repository, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }

        $data    = $request->getJournalData();
        $journal = $repository->update($journal, $data);
        $this->attachments->saveAttachmentsForModel($journal);

        // flash errors
        if (count($this->attachments->getErrors()->get('attachments')) > 0) {
            Session::flash('error', $this->attachments->getErrors()->get('attachments'));
        }
        // flash messages
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        event(new UpdatedTransactionJournal($journal));
        // update, get events by date and sort DESC

        $type = strtolower(TransactionJournal::transactionTypeStr($journal));
        Session::flash('success', strval(trans('firefly.updated_' . $type, ['description' => e($data['description'])])));
        Preferences::mark();

        // if wishes to split:


        if (intval($request->get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit.fromUpdate', true);

            return redirect(route('transactions.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('transactions.edit.url'));

    }
}
