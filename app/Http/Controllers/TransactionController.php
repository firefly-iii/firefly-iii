<?php
/**
 * TransactionController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Http\Controllers;

use Amount;
use Auth;
use Carbon\Carbon;
use DB;
use ExpandedForm;
use FireflyIII\Events\TransactionJournalStored;
use FireflyIII\Events\TransactionJournalUpdated;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Log;
use Preferences;
use Response;
use Session;
use Steam;
use URL;
use View;

/**
 * Class TransactionController
 *
 * @package FireflyIII\Http\Controllers
 */
class TransactionController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.transactions'));
        View::share('mainTitleIcon', 'fa-repeat');
    }

    /**
     * @param ARI    $repository
     * @param string $what
     *
     * @return \Illuminate\View\View
     */
    public function create(ARI $repository, string $what = TransactionType::DEPOSIT)
    {
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);

        /** @var PiggyBankRepositoryInterface $piggyRepository */
        $piggyRepository = app(PiggyBankRepositoryInterface::class);

        $what          = strtolower($what);
        $uploadSize    = min(Steam::phpBytes(ini_get('upload_max_filesize')), Steam::phpBytes(ini_get('post_max_size')));
        $assetAccounts = ExpandedForm::makeSelectList($repository->getAccounts(['Default account', 'Asset account']));
        $budgets       = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        $piggyBanks    = $piggyRepository->getPiggyBanks();
        /** @var PiggyBank $piggy */
        foreach ($piggyBanks as $piggy) {
            $piggy->name = $piggy->name . ' (' . Amount::format($piggy->currentRelevantRep()->currentamount, false) . ')';
        }

        $piggies   = ExpandedForm::makeSelectListWithEmpty($piggyBanks);
        $preFilled = Session::has('preFilled') ? session('preFilled') : [];
        $subTitle  = trans('form.add_new_' . $what);

        Session::put('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (session('transactions.create.fromStore') !== true) {
            Session::put('transactions.create.url', URL::previous());
        }
        Session::forget('transactions.create.fromStore');
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'create-' . $what);

        asort($piggies);


        return view('transactions.create', compact('assetAccounts', 'uploadSize', 'budgets', 'what', 'piggies', 'subTitle'));
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\View\View
     */
    public function delete(TransactionJournal $journal)
    {
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
        $type = strtolower($transactionJournal->transaction_type_type ?? TransactionJournal::transactionTypeStr($transactionJournal));
        Session::flash('success', strval(trans('firefly.deleted_' . $type, ['description' => e($transactionJournal->description)])));

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
        $count = $journal->transactions()->count();
        if ($count > 2) {
            return redirect(route('split.journal.edit', [$journal->id]));
        }
        /** @var ARI $accountRepository */
        $accountRepository = app(ARI::class);
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        /** @var PiggyBankRepositoryInterface $piggyRepository */
        $piggyRepository = app(PiggyBankRepositoryInterface::class);

        $assetAccounts = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));
        $budgetList    = ExpandedForm::makeSelectListWithEmpty($budgetRepository->getActiveBudgets());
        $piggyBankList = ExpandedForm::makeSelectListWithEmpty($piggyRepository->getPiggyBanks());
        $maxFileSize   = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize   = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize    = min($maxFileSize, $maxPostSize);
        $what          = strtolower(TransactionJournal::transactionTypeStr($journal));
        $subTitle      = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);


        $preFilled = [
            'date'                     => TransactionJournal::dateAsString($journal),
            'interest_date'            => TransactionJournal::dateAsString($journal, 'interest_date'),
            'book_date'                => TransactionJournal::dateAsString($journal, 'book_date'),
            'process_date'             => TransactionJournal::dateAsString($journal, 'process_date'),
            'category'                 => TransactionJournal::categoryAsString($journal),
            'budget_id'                => TransactionJournal::budgetId($journal),
            'piggy_bank_id'            => TransactionJournal::piggyBankId($journal),
            'tags'                     => join(',', $journal->tags->pluck('tag')->toArray()),
            'source_account_id'        => TransactionJournal::sourceAccount($journal)->id,
            'source_account_name'      => TransactionJournal::sourceAccount($journal)->name,
            'destination_account_id'   => TransactionJournal::destinationAccount($journal)->id,
            'destination_account_name' => TransactionJournal::destinationAccount($journal)->name,
            'amount'                   => TransactionJournal::amountPositive($journal),
        ];

        if ($journal->isWithdrawal() && TransactionJournal::destinationAccountTypeStr($journal) == 'Cash account') {
            $preFilled['destination_account_name'] = '';
        }
        if ($journal->isDeposit() && TransactionJournal::sourceAccountTypeStr($journal) == 'Cash account') {
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


        return view('transactions.edit', compact('journal', 'uploadSize', 'assetAccounts', 'what', 'budgetList', 'piggyBankList', 'subTitle'))->with(
            'data', $preFilled
        );
    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return \Illuminate\View\View
     */
    public function index(JournalRepositoryInterface $repository, string $what)
    {
        $pageSize     = Preferences::get('transactionPageSize', 50)->data;
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what);
        $page         = intval(Input::get('page'));
        $journals     = $repository->getJournals($types, $page, $pageSize);

        $journals->setPath('transactions/' . $what);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

    }

    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function massDelete(Collection $journals)
    {
        $subTitle = trans('firefly.mass_delete_journals');

        // put previous url in session
        Session::put('transactions.mass-delete.url', URL::previous());
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'mass-delete');

        return view('transactions.mass-delete', compact('journals', 'subTitle'));

    }

    /**
     * @param MassDeleteJournalRequest   $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function massDestroy(MassDeleteJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $ids = $request->get('confirm_mass_delete');
        $set = new Collection;
        if (is_array($ids)) {
            /** @var int $journalId */
            foreach ($ids as $journalId) {
                /** @var TransactionJournal $journal */
                $journal = $repository->find($journalId);
                if (!is_null($journal->id) && $journalId == $journal->id) {
                    $set->push($journal);
                }
            }
        }
        unset($journal);
        $count = 0;

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $repository->delete($journal);
            $count++;
        }

        Preferences::mark();
        Session::flash('success', trans('firefly.mass_deleted_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect(session('transactions.mass-delete.url'));

    }


    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function massEdit(Collection $journals)
    {
        $subTitle = trans('firefly.mass_edit_journals');
        /** @var ARI $accountRepository */
        $accountRepository = app(ARI::class);
        $accountList       = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));

        // put previous url in session
        Session::put('transactions.mass-edit.url', URL::previous());
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'mass-edit');

        return view('transactions.mass-edit', compact('journals', 'subTitle', 'accountList'));
    }

    /**
     * @param MassEditJournalRequest     $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function massUpdate(MassEditJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $journalIds = Input::get('journals');
        $count      = 0;
        if (is_array($journalIds)) {
            foreach ($journalIds as $journalId) {
                $journal = $repository->find(intval($journalId));
                if ($journal) {
                    // do update.

                    // get optional fields:
                    $what            = strtolower(TransactionJournal::transactionTypeStr($journal));
                    $sourceAccountId = $request->get('source_account_id')[$journal->id] ??  0;
                    $destAccountId   = $request->get('destination_account_id')[$journal->id] ??  0;
                    $expenseAccount  = $request->get('expense_account')[$journal->id] ?? '';
                    $revenueAccount  = $request->get('revenue_account')[$journal->id] ?? '';
                    $budgetId        = $journal->budgets->first() ? $journal->budgets->first()->id : 0;
                    $category        = $journal->categories->first() ? $journal->categories->first()->name : '';
                    $tags            = $journal->tags->pluck('tag')->toArray();

                    // for a deposit, the 'account_id' is the account the money is deposited on.
                    // needs a better way of handling.
                    // more uniform source/destination field names
                    $accountId = $sourceAccountId;
                    if ($what == 'deposit') {
                        $accountId = $destAccountId;
                    }

                    // build data array
                    $data = [
                        'id'                        => $journal->id,
                        'what'                      => $what,
                        'description'               => $request->get('description')[$journal->id],
                        'account_id'                => intval($accountId),
                        'account_from_id'           => intval($sourceAccountId),
                        'account_to_id'             => intval($destAccountId),
                        'expense_account'           => $expenseAccount,
                        'revenue_account'           => $revenueAccount,
                        'amount'                    => round($request->get('amount')[$journal->id], 4),
                        'user'                      => Auth::user()->id,
                        'amount_currency_id_amount' => intval($request->get('amount_currency_id_amount_' . $journal->id)),
                        'date'                      => new Carbon($request->get('date')[$journal->id]),
                        'interest_date'             => $journal->interest_date,
                        'book_date'                 => $journal->book_date,
                        'process_date'              => $journal->process_date,
                        'budget_id'                 => $budgetId,
                        'category'                  => $category,
                        'tags'                      => $tags,

                    ];
                    // call repository update function.
                    $repository->update($journal, $data);

                    $count++;
                }
            }
        }
        Preferences::mark();
        Session::flash('success', trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect(session('transactions.mass-edit.url'));

    }

    /**
     * @param JournalRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function reorder(JournalRepositoryInterface $repository)
    {
        $ids  = Input::get('items');
        $date = new Carbon(Input::get('date'));
        if (count($ids) > 0) {
            $order = 0;
            foreach ($ids as $id) {

                $journal = $repository->find($id);
                if ($journal && $journal->date->format('Y-m-d') == $date->format('Y-m-d')) {
                    $journal->order = $order;
                    $order++;
                    $journal->save();
                }
            }
        }
        Preferences::mark();

        return Response::json([true]);

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\View\View
     */
    public function show(TransactionJournal $journal)
    {

        /** @var Collection $set */
        $events = $journal->piggyBankEvents()->get();
        $events->each(
            function (PiggyBankEvent $event) {
                $event->piggyBank = $event->piggyBank()->withTrashed()->first();
            }
        );

        // TODO different for each transaction type!
        $transactions = $journal->transactions()->groupBy('transactions.account_id')->orderBy('amount', 'ASC')->get(
            ['transactions.*', DB::raw('SUM(`transactions`.`amount`) as `sum`')]
        );
        $what         = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle     = trans('firefly.' . $what) . ' "' . e($journal->description) . '"';

        return view('transactions.show', compact('journal', 'events', 'subTitle', 'what', 'transactions'));
    }

    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     *
     * @param AttachmentHelperInterface  $att
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(JournalFormRequest $request, JournalRepositoryInterface $repository, AttachmentHelperInterface $att)
    {
        Log::debug('Start of store.');
        $doSplit     = intval($request->get('split_journal')) === 1;
        $journalData = $request->getJournalData();
        if ($doSplit) {
            // put all journal data in the session and redirect to split routine.
            Session::put('temporary_split_data', $journalData);

            return redirect(route('split.journal.from-store'));
        }
        Log::debug('Not in split.');

        // if not withdrawal, unset budgetid.
        if ($journalData['what'] != strtolower(TransactionType::WITHDRAWAL)) {
            $journalData['budget_id'] = 0;
        }

        $journal = $repository->store($journalData);

        $att->saveAttachmentsForModel($journal);

        // flash errors
        if (count($att->getErrors()->get('attachments')) > 0) {
            Session::flash('error', $att->getErrors()->get('attachments'));
        }
        // flash messages
        if (count($att->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $att->getMessages()->get('attachments'));
        }

        event(new TransactionJournalStored($journal, intval($journalData['piggy_bank_id'])));

        Session::flash('success', strval(trans('firefly.stored_journal', ['description' => e($journal->description)])));
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('transactions.create.fromStore', true);

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('transactions.create.url'));

    }


    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     * @param AttachmentHelperInterface  $att
     * @param TransactionJournal         $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(JournalFormRequest $request, JournalRepositoryInterface $repository, AttachmentHelperInterface $att, TransactionJournal $journal)
    {
        $journalData = $request->getJournalData();
        Log::debug('Will update journal ', $journal->toArray());
        Log::debug('Update related data ', $journalData);
        $repository->update($journal, $journalData);

        // save attachments:
        $att->saveAttachmentsForModel($journal);

        // flash errors
        if (count($att->getErrors()->get('attachments')) > 0) {
            Session::flash('error', $att->getErrors()->get('attachments'));
        }
        // flash messages
        if (count($att->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $att->getMessages()->get('attachments'));
        }

        event(new TransactionJournalUpdated($journal));
        // update, get events by date and sort DESC

        $type = strtolower($journal->transaction_type_type ?? TransactionJournal::transactionTypeStr($journal));
        Session::flash('success', strval(trans('firefly.updated_' . $type, ['description' => e($journalData['description'])])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit.fromUpdate', true);

            return redirect(route('transactions.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('transactions.edit.url'));

    }
}
