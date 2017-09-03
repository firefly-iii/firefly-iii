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

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;


use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Log;
use Preferences;
use Session;
use Steam;
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
    /** @var  CurrencyRepositoryInterface */
    private $currency;
    /** @var  PiggyBankRepositoryInterface */
    private $piggyBanks;

    /** @var  JournalRepositoryInterface */
    private $repository;

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
                $this->currency    = app(CurrencyRepositoryInterface::class);
                $this->repository  = app(JournalRepositoryInterface::class);

                View::share('title', trans('firefly.transactions'));
                View::share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );

    }

    public function cloneTransaction(TransactionJournal $journal)
    {
        $source       = $journal->sourceAccountList()->first();
        $destination  = $journal->destinationAccountList()->first();
        $budget       = $journal->budgets()->first();
        $budgetId     = is_null($budget) ? 0 : $budget->id;
        $category     = $journal->categories()->first();
        $categoryName = is_null($category) ? '' : $category->name;
        $tags         = join(',', $journal->tags()->get()->pluck('tag')->toArray());
        /** @var Transaction $transaction */
        $transaction   = $journal->transactions()->first();
        $amount        = Steam::positive($transaction->amount);
        $foreignAmount = is_null($transaction->foreign_amount) ? null : Steam::positive($transaction->foreign_amount);

        $preFilled = [
            'description'               => $journal->description,
            'source_account_id'         => $source->id,
            'source_account_name'       => $source->name,
            'destination_account_id'    => $destination->id,
            'destination_account_name'  => $destination->name,
            'amount'                    => $amount,
            'source_amount'             => $amount,
            'destination_amount'        => $foreignAmount,
            'foreign_amount'            => $foreignAmount,
            'native_amount'             => $foreignAmount,
            'amount_currency_id_amount' => $transaction->foreign_currency_id ?? 0,
            'date'                      => (new Carbon())->format('Y-m-d'),
            'budget_id'                 => $budgetId,
            'category'                  => $categoryName,
            'tags'                      => $tags,
            'interest_date'             => $journal->getMeta('interest_date'),
            'book_date'                 => $journal->getMeta('book_date'),
            'process_date'              => $journal->getMeta('process_date'),
            'due_date'                  => $journal->getMeta('due_date'),
            'payment_date'              => $journal->getMeta('payment_date'),
            'invoice_date'              => $journal->getMeta('invoice_date'),
            'internal_reference'        => $journal->getMeta('internal_reference'),
            'notes'                     => $journal->getMeta('notes'),
        ];
        Session::flash('preFilled', $preFilled);

        return redirect(route('transactions.create', [strtolower($journal->transactionType->type)]));
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
        $assetAccounts  = $this->groupedActiveAccountList();
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
            $this->rememberPreviousUri('transactions.create.uri');
        }
        Session::forget('transactions.create.fromStore');
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'create-' . $what);

        asort($piggies);

        return view(
            'transactions.single.create',
            compact('assetAccounts', 'subTitleIcon', 'uploadSize', 'budgets', 'what', 'piggies', 'subTitle', 'optionalFields', 'preFilled')
        );
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(TransactionJournal $journal)
    {
        // Covered by another controller's tests
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd

        $what     = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle = trans('firefly.delete_' . $what, ['description' => $journal->description]);

        // put previous url in session
        $this->rememberPreviousUri('transactions.delete.uri');
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'delete-' . $what);

        return view('transactions.single.delete', compact('journal', 'subTitle', 'what'));


    }

    /**
     * @param TransactionJournal $transactionJournal
     *
     * @return \Illuminate\Http\RedirectResponse
     * @internal param JournalRepositoryInterface $repository
     */
    public function destroy(TransactionJournal $transactionJournal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($transactionJournal)) {
            return $this->redirectToAccount($transactionJournal);
        }
        // @codeCoverageIgnoreEnd
        $type = $transactionJournal->transactionTypeStr();
        Session::flash('success', strval(trans('firefly.deleted_' . strtolower($type), ['description' => e($transactionJournal->description)])));

        $this->repository->delete($transactionJournal);

        Preferences::mark();

        return redirect($this->getPreviousUri('transactions.delete.uri'));
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return mixed
     */
    public function edit(TransactionJournal $journal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd
        if ($this->isSplitJournal($journal)) {
            return redirect(route('transactions.split.edit', [$journal->id]));
        }

        $what          = strtolower($journal->transactionTypeStr());
        $assetAccounts = $this->groupedAccountList();
        $budgetList    = ExpandedForm::makeSelectListWithEmpty($this->budgets->getBudgets());

        // view related code
        $subTitle = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);

        // journal related code
        $sourceAccounts      = $journal->sourceAccountList();
        $destinationAccounts = $journal->destinationAccountList();
        $optionalFields      = Preferences::get('transaction_journal_optional_fields', [])->data;
        $pTransaction        = $journal->positiveTransaction();
        $foreignCurrency     = !is_null($pTransaction->foreignCurrency) ? $pTransaction->foreignCurrency : $pTransaction->transactionCurrency;
        $preFilled           = [
            'date'                     => $journal->dateAsString(),
            'interest_date'            => $journal->dateAsString('interest_date'),
            'book_date'                => $journal->dateAsString('book_date'),
            'process_date'             => $journal->dateAsString('process_date'),
            'category'                 => $journal->categoryAsString(),
            'budget_id'                => $journal->budgetId(),
            'tags'                     => join(',', $journal->tags->pluck('tag')->toArray()),
            'source_account_id'        => $sourceAccounts->first()->id,
            'source_account_name'      => $sourceAccounts->first()->edit_name,
            'destination_account_id'   => $destinationAccounts->first()->id,
            'destination_account_name' => $destinationAccounts->first()->edit_name,

            // new custom fields:
            'due_date'                 => $journal->dateAsString('due_date'),
            'payment_date'             => $journal->dateAsString('payment_date'),
            'invoice_date'             => $journal->dateAsString('invoice_date'),
            'interal_reference'        => $journal->getMeta('internal_reference'),
            'notes'                    => $journal->getMeta('notes'),

            // amount fields
            'amount'                   => $pTransaction->amount,
            'source_amount'            => $pTransaction->amount,
            'native_amount'            => $pTransaction->amount,
            'destination_amount'       => $pTransaction->foreign_amount,
            'currency'                 => $pTransaction->transactionCurrency,
            'source_currency'          => $pTransaction->transactionCurrency,
            'native_currency'          => $pTransaction->transactionCurrency,
            'foreign_currency'         => $foreignCurrency,
            'destination_currency'     => $foreignCurrency,
        ];

        // amounts for withdrawals and deposits:
        // amount, native_amount, source_amount, destination_amount
        if (($journal->isWithdrawal() || $journal->isDeposit()) && !is_null($pTransaction->foreign_amount)) {
            $preFilled['amount']   = $pTransaction->foreign_amount;
            $preFilled['currency'] = $pTransaction->foreignCurrency;
        }

        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-' . $what);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('transactions.edit.fromUpdate') !== true) {
            $this->rememberPreviousUri('transactions.edit.uri');
        }
        Session::forget('transactions.edit.fromUpdate');

        return view(
            'transactions.single.edit',
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

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($journal, $files);

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

        // @codeCoverageIgnoreStart
        if ($createAnother === true) {
            Session::put('transactions.create.fromStore', true);

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        if ($doSplit === true) {
            return redirect(route('transactions.split.edit', [$journal->id]));
        }

        // @codeCoverageIgnoreEnd

        return redirect($this->getPreviousUri('transactions.create.uri'));
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
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd

        $data    = $request->getJournalData();
        $journal = $repository->update($journal, $data);
        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        $this->attachments->saveAttachmentsForModel($journal, $files);

        // @codeCoverageIgnoreStart
        if (count($this->attachments->getErrors()->get('attachments')) > 0) {
            Session::flash('error', $this->attachments->getErrors()->get('attachments'));
        }
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        // @codeCoverageIgnoreEnd

        event(new UpdatedTransactionJournal($journal));
        // update, get events by date and sort DESC

        $type = strtolower($journal->transactionTypeStr());
        Session::flash('success', strval(trans('firefly.updated_' . $type, ['description' => e($data['description'])])));
        Preferences::mark();

        // @codeCoverageIgnoreStart
        if (intval($request->get('return_to_edit')) === 1) {
            Session::put('transactions.edit.fromUpdate', true);

            return redirect(route('transactions.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('transactions.edit.uri'));
    }

    /**
     * @return array
     */
    private function groupedActiveAccountList(): array
    {
        $accounts = $this->accounts->getActiveAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $return   = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $type = $account->getMeta('accountRole');
            if (strlen($type) === 0) {
                $type = 'no_account_type';
            }
            $key                        = strval(trans('firefly.opt_group_' . $type));
            $return[$key][$account->id] = $account->name;
        }

        return $return;
    }

    /**
     * @return array
     */
    private function groupedAccountList(): array
    {
        $accounts = $this->accounts->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $return   = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $type = $account->getMeta('accountRole');
            if (strlen($type) === 0) {
                $type = 'no_account_type';
            }
            $key                        = strval(trans('firefly.opt_group_' . $type));
            $return[$key][$account->id] = $account->name;
        }

        return $return;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    private function isSplitJournal(TransactionJournal $journal): bool
    {
        $count = $this->repository->countTransactions($journal);

        if ($count > 2) {
            return true;
        }

        return false;
    }
}
