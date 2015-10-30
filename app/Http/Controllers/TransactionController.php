<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Auth;
use Carbon\Carbon;
use Config;
use ExpandedForm;
use FireflyIII\Events\JournalCreated;
use FireflyIII\Events\JournalSaved;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Input;
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
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', trans('firefly.transactions'));
        View::share('mainTitleIcon', 'fa-repeat');
    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param string                     $what
     *
     * @return \Illuminate\View\View
     */
    public function create(AccountRepositoryInterface $repository, $what = 'deposit')
    {
        $maxFileSize = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        $accounts    = ExpandedForm::makeSelectList($repository->getAccounts(['Default account', 'Asset account']));
        $budgets     = ExpandedForm::makeSelectList(Auth::user()->budgets()->get());
        $budgets[0]  = trans('form.noBudget');

        // piggy bank list:
        $piggyBanks = Auth::user()->piggyBanks()->orderBy('order', 'ASC')->get();
        /** @var PiggyBank $piggy */
        foreach ($piggyBanks as $piggy) {
            $piggy->name = $piggy->name . ' (' . Amount::format($piggy->currentRelevantRep()->currentamount, false) . ')';
        }

        $piggies    = ExpandedForm::makeSelectList($piggyBanks);
        $piggies[0] = trans('form.noPiggybank');
        $preFilled  = Session::has('preFilled') ? Session::get('preFilled') : [];
        $respondTo  = ['account_id', 'account_from_id'];
        $subTitle   = trans('form.add_new_' . $what);

        foreach ($respondTo as $r) {
            $preFilled[$r] = Input::get($r);
        }
        Session::put('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('transactions.create.fromStore') !== true) {
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
        $what     = strtolower($journal->transactionType->type);
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
        Session::flash('success', 'Transaction "' . e($transactionJournal->description) . '" destroyed.');

        $repository->delete($transactionJournal);

        Preferences::mark();

        // redirect to previous URL:
        return redirect(Session::get('transactions.delete.url'));
    }

    /**
     * Shows the view to edit a transaction.
     *
     * @param AccountRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return $this
     */
    public function edit(AccountRepositoryInterface $repository, TransactionJournal $journal)
    {
        // cannot edit opening balance
        if ($journal->transactionType->type == 'Opening balance') {
            return view('error')->with('message', 'Cannot edit this transaction. Edit the account instead!');
        }


        $maxFileSize = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize  = min($maxFileSize, $maxPostSize);
        $what        = strtolower($journal->transactionType->type);
        $accounts    = ExpandedForm::makeSelectList($repository->getAccounts(['Default account', 'Asset account']));
        $budgets     = ExpandedForm::makeSelectList(Auth::user()->budgets()->get());
        $budgets[0]  = trans('form.noBudget');
        $piggies     = ExpandedForm::makeSelectList(Auth::user()->piggyBanks()->get());
        $piggies[0]  = trans('form.noPiggybank');
        $subTitle    = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);
        $preFilled   = [
            'date'          => $journal->date->format('Y-m-d'),
            'category'      => '',
            'budget_id'     => 0,
            'piggy_bank_id' => 0
        ];
        // get tags:
        $tags = [];
        foreach ($journal->tags as $tag) {
            $tags[] = $tag->tag;
        }
        $preFilled['tags'] = join(',', $tags);

        $category = $journal->categories()->first();
        if (!is_null($category)) {
            $preFilled['category'] = $category->name;
        }

        $budget = $journal->budgets()->first();
        if (!is_null($budget)) {
            $preFilled['budget_id'] = $budget->id;
        }

        if ($journal->piggyBankEvents()->count() > 0) {
            $preFilled['piggy_bank_id'] = $journal->piggyBankEvents()->orderBy('date', 'DESC')->first()->piggy_bank_id;
        }

        $preFilled['amount'] = $journal->amount_positive;

        if ($journal->transactionType->type == 'Withdrawal') {
            $preFilled['account_id']      = $journal->source_account->id;
            $preFilled['expense_account'] = $journal->destination_account->name_for_editform;
        } else {
            $preFilled['account_id']      = $journal->destination_account->id;
            $preFilled['revenue_account'] = $journal->source_account->name_for_editform;
        }

        $preFilled['account_from_id'] = $journal->source_account->id;
        $preFilled['account_to_id']   = $journal->destination_account->id;

        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'transactions');
        Session::flash('gaEventAction', 'edit-' . $what);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('transactions.edit.fromUpdate') !== true) {
            Session::put('transactions.edit.url', URL::previous());
        }
        Session::forget('transactions.edit.fromUpdate');


        return view('transactions.edit', compact('journal', 'uploadSize', 'accounts', 'what', 'budgets', 'piggies', 'subTitle'))->with('data', $preFilled);
    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return \Illuminate\View\View
     */
    public function index(JournalRepositoryInterface $repository, $what)
    {
        $subTitleIcon = Config::get('firefly.transactionIconsByWhat.' . $what);
        $types        = Config::get('firefly.transactionTypesByWhat.' . $what);
        $subTitle     = trans('firefly.title_' . $what);
        $page         = intval(Input::get('page'));
        $offset       = $page > 0 ? ($page - 1) * 50 : 0;
        $journals     = $repository->getJournalsOfTypes($types, $offset, $page);

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
        bcscale(2);
        $journal->transactions->each(
            function (Transaction $t) use ($journal, $repository) {
                $t->before = $repository->getAmountBefore($journal, $t);
                $t->after  = bcadd($t->before, $t->amount);
            }
        );
        $what     = strtolower($journal->transactionType->type);
        $subTitle = trans('firefly.' . $journal->transactionType->type) . ' "' . e($journal->description) . '"';

        return view('transactions.show', compact('journal', 'subTitle', 'what'));
    }

    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(JournalFormRequest $request, JournalRepositoryInterface $repository, AttachmentHelperInterface $att)
    {

        $journalData = $request->getJournalData();

        // if not withdrawal, unset budgetid.
        if ($journalData['what'] != 'withdrawal') {
            $journalData['budget_id'] = 0;
        }

        $journal = $repository->store($journalData);

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

        // rescan journal, UpdateJournalConnection
        event(new JournalSaved($journal));

        if ($journal->transactionType->type == 'Transfer' && intval($request->get('piggy_bank_id')) > 0) {
            event(new JournalCreated($journal, intval($request->get('piggy_bank_id'))));
        }

        Session::flash('success', 'New transaction "' . $journal->description . '" stored!');
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('transactions.create.fromStore', true);

            return redirect(route('transactions.create', [$request->input('what')]))->withInput();
        }

        // redirect to previous URL.
        return redirect(Session::get('transactions.create.url'));

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

        // cannot edit opening balance
        if ($journal->transactionType->type == 'Opening balance') {
            return view('error')->with('message', 'Cannot edit this transaction. Edit the account instead!');
        }

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

        event(new JournalSaved($journal));
        // update, get events by date and sort DESC

        Session::flash('success', 'Transaction "' . e($journalData['description']) . '" updated.');
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit.fromUpdate', true);

            return redirect(route('transactions.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(Session::get('transactions.edit.url'));

    }

}
