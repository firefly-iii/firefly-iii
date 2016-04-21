<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Auth;
use Carbon\Carbon;
use Config;
use ExpandedForm;
use FireflyIII\Events\TransactionJournalStored;
use FireflyIII\Events\TransactionJournalUpdated;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
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
        $what        = strtolower($what);
        $maxFileSize = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        $accounts    = ExpandedForm::makeSelectList($repository->getAccounts(['Default account', 'Asset account']));
        $budgets     = ExpandedForm::makeSelectList(Auth::user()->budgets()->get());
        $budgets[0]  = trans('firefly.no_budget');
        $piggyBanks  = Auth::user()->piggyBanks()->orderBy('order', 'ASC')->get();
        /** @var PiggyBank $piggy */
        foreach ($piggyBanks as $piggy) {
            $piggy->name = $piggy->name . ' (' . Amount::format($piggy->currentRelevantRep()->currentamount, false) . ')';
        }

        $piggies    = ExpandedForm::makeSelectList($piggyBanks);
        $piggies[0] = trans('form.noPiggybank');
        $preFilled  = Session::has('preFilled') ? session('preFilled') : [];
        $respondTo  = ['account_id', 'account_from_id'];
        $subTitle   = trans('form.add_new_' . $what);

        foreach ($respondTo as $r) {
            $preFilled[$r] = Input::get($r);
        }
        Session::put('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (session('transactions.create.fromStore') !== true) {
            Session::put('transactions.create.url', URL::previous());
        }
        Session::forget('transactions.create.fromStore');
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'create-' . $what);

        asort($piggies);


        return view('transactions.create', compact('accounts', 'uploadSize', 'budgets', 'what', 'piggies', 'subTitle'));
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
        /** @var ARI $accountRepository */
        $accountRepository = app('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app('FireflyIII\Repositories\Budget\BudgetRepositoryInterface');
        /** @var PiggyBankRepositoryInterface $piggyRepository */
        $piggyRepository = app('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');

        $accountList      = ExpandedForm::makeSelectList($accountRepository->getAccounts(['Default account', 'Asset account']));
        $budgetList       = ExpandedForm::makeSelectList($budgetRepository->getActiveBudgets());
        $piggyBankList    = ExpandedForm::makeSelectList($piggyRepository->getPiggyBanks());
        $budgetList[0]    = trans('firefly.no_budget');
        $piggyBankList[0] = trans('form.noPiggybank');
        $maxFileSize      = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize      = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize       = min($maxFileSize, $maxPostSize);
        $what             = strtolower(TransactionJournal::transactionTypeStr($journal));
        $subTitle         = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);


        $preFilled = [
            'date'            => TransactionJournal::dateAsString($journal),
            'interest_date'   => TransactionJournal::dateAsString($journal, 'interest_date'),
            'book_date'       => TransactionJournal::dateAsString($journal, 'book_date'),
            'process_date'    => TransactionJournal::dateAsString($journal, 'process_date'),
            'category'        => TransactionJournal::categoryAsString($journal),
            'budget_id'       => TransactionJournal::budgetId($journal),
            'piggy_bank_id'   => TransactionJournal::piggyBankId($journal),
            'tags'            => join(',', $journal->tags->pluck('tag')->toArray()),
            'account_from_id' => TransactionJournal::sourceAccount($journal)->id,
            'account_to_id'   => TransactionJournal::destinationAccount($journal)->id,
            'amount'          => TransactionJournal::amountPositive($journal),
        ];

        if ($journal->isWithdrawal()) {
            $preFilled['account_id'] = TransactionJournal::sourceAccount($journal)->id;
            if (TransactionJournal::destinationAccountTypeStr($journal) != 'Cash account') {
                $preFilled['expense_account'] = TransactionJournal::destinationAccount($journal)->name;
            }
        } else {
            $preFilled['account_id'] = TransactionJournal::destinationAccount($journal)->id;
            if (TransactionJournal::sourceAccountTypeStr($journal) != 'Cash account') {
                $preFilled['revenue_account'] = TransactionJournal::sourceAccount($journal)->name;
            }
        }


        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-' . $what);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('transactions.edit.fromUpdate') !== true) {
            Session::put('transactions.edit.url', URL::previous());
        }
        Session::forget('transactions.edit.fromUpdate');


        return view('transactions.edit', compact('journal', 'uploadSize', 'accountList', 'what', 'budgetList', 'piggyBankList', 'subTitle'))->with(
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
        $subTitleIcon = Config::get('firefly.transactionIconsByWhat.' . $what);
        $types        = Config::get('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what);
        $page         = intval(Input::get('page'));
        $journals     = $repository->getJournalsOfTypes($types, $page, $pageSize);

        $journals->setPath('transactions/' . $what);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

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

                $journal = $repository->getWithDate($id, $date);
                if ($journal) {
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
     * @param JournalRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return \Illuminate\View\View
     */
    public function show(JournalRepositoryInterface $repository, TransactionJournal $journal)
    {

        /** @var Collection $set */
        $events = $journal->piggyBankEvents()->get();
        $events->each(
            function (PiggyBankEvent $event) {
                $event->piggyBank = $event->piggyBank()->withTrashed()->first();
            }
        );

        $journal->transactions->each(
            function (Transaction $t) use ($journal, $repository) {
                $t->before = $repository->getAmountBefore($journal, $t);
                $t->after  = bcadd($t->before, $t->amount);
            }
        );
        $what     = strtolower($journal->transaction_type_type ?? $journal->transactionType->type);
        $subTitle = trans('firefly.' . $what) . ' "' . e($journal->description) . '"';

        return view('transactions.show', compact('journal', 'events', 'subTitle', 'what'));
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
        $journalData = $request->getJournalData();

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

        Log::debug('Triggered TransactionJournalStored with transaction journal #' . $journal->id . ' and piggy #' . intval($request->get('piggy_bank_id')));
        event(new TransactionJournalStored($journal, intval($request->get('piggy_bank_id'))));

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
