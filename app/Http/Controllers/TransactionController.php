<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Events\JournalCreated;
use FireflyIII\Events\JournalSaved;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\JournalFormRequest;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Input;
use Redirect;
use Response;
use Session;
use URL;
use View;
use Log;

/**
 * Class TransactionController
 *
 * @package FireflyIII\Http\Controllers
 */
class TransactionController extends Controller
{
    /**
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', 'Transactions');
        View::share('mainTitleIcon', 'fa-repeat');
    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param string                     $what
     *
     * @return View
     */
    public function create(AccountRepositoryInterface $repository, $what = 'deposit')
    {
        $accounts   = ExpandedForm::makeSelectList($repository->getAccounts(['Default account', 'Asset account']));
        $budgets    = ExpandedForm::makeSelectList(Auth::user()->budgets()->get());
        $budgets[0] = '(no budget)';
        $piggies    = ExpandedForm::makeSelectList(Auth::user()->piggyBanks()->get());
        $piggies[0] = '(no piggy bank)';
        $preFilled  = Session::has('preFilled') ? Session::get('preFilled') : [];
        $respondTo  = ['account_id', 'account_from_id'];
        $subTitle   = 'Add a new ' . e($what);

        foreach ($respondTo as $r) {
            if (!is_null(Input::get($r))) {
                $preFilled[$r] = Input::get($r);
            }
        }
        Session::put('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "create another").
        if (Session::get('transactions.create.fromStore') !== true) {
            Session::put('transactions.create.url', URL::previous());
        }
        Session::forget('transactions.create.fromStore');

        asort($piggies);


        return view('transactions.create', compact('accounts', 'budgets', 'what', 'piggies', 'subTitle'));
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function delete(TransactionJournal $journal)
    {
        $type     = strtolower($journal->transactionType->type);
        $subTitle = 'Delete ' . e($type) . ' "' . e($journal->description) . '"';

        // put previous url in session
        Session::put('transactions.delete.url', URL::previous());

        return view('transactions.delete', compact('journal', 'subTitle'));


    }

    /**
     * @param TransactionJournal $transactionJournal
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(JournalRepositoryInterface $repository, TransactionJournal $transactionJournal)
    {
        Session::flash('success', 'Transaction "' . e($transactionJournal->description) . '" destroyed.');

        $repository->delete($transactionJournal);

        // redirect to previous URL:
        return Redirect::to(Session::get('transactions.delete.url'));
    }

    /**
     * Shows the view to edit a transaction.
     *
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function edit(AccountRepositoryInterface $repository, TransactionJournal $journal)
    {
        $what         = strtolower($journal->transactiontype->type);
        $accounts     = ExpandedForm::makeSelectList($repository->getAccounts(['Default account', 'Asset account']));
        $budgets      = ExpandedForm::makeSelectList(Auth::user()->budgets()->get());
        $budgets[0]   = '(no budget)';
        $transactions = $journal->transactions()->orderBy('amount', 'DESC')->get();
        $piggies      = ExpandedForm::makeSelectList(Auth::user()->piggyBanks()->get());
        $piggies[0]   = '(no piggy bank)';
        $preFilled    = [
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

        $preFilled['amount']          = $journal->amount;
        $preFilled['account_id']      = $journal->assetAccount->id;
        $preFilled['expense_account'] = $transactions[0]->account->name;
        $preFilled['revenue_account'] = $transactions[1]->account->name;
        $preFilled['account_from_id'] = $transactions[1]->account->id;
        $preFilled['account_to_id']   = $transactions[0]->account->id;

        Session::flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (Session::get('transactions.edit.fromUpdate') !== true) {
            Session::put('transactions.edit.url', URL::previous());
        }
        Session::forget('transactions.edit.fromUpdate');


        return View::make('transactions.edit', compact('journal', 'accounts', 'what', 'budgets', 'piggies', 'subTitle'))->with('data', $preFilled);
    }

    /**
     * @param JournalRepositoryInterface $repository
     * @param                            $what
     *
     * @return View
     */
    public function index(JournalRepositoryInterface $repository, $what)
    {
        $types = [];
        switch ($what) {
            case 'expenses':
            case 'withdrawal':
                $subTitleIcon = 'fa-long-arrow-left';
                $subTitle     = 'Expenses';
                $types        = ['Withdrawal'];
                break;
            case 'revenue':
            case 'deposit':
                $subTitleIcon = 'fa-long-arrow-right';
                $subTitle     = 'Revenue, income and deposits';
                $types        = ['Deposit'];
                break;
            case 'transfer':
            case 'transfers':
                $subTitleIcon = 'fa-exchange';
                $subTitle     = 'Transfers';
                $types        = ['Transfer'];
                break;
        }

        $page     = intval(Input::get('page'));
        $offset   = $page > 0 ? ($page - 1) * 50 : 0;
        $journals = $repository->getJournalsOfTypes($types, $offset, $page);

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

        return Response::json(true);

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function show(JournalRepositoryInterface $repository, TransactionJournal $journal)
    {
        $journal->transactions->each(
            function (Transaction $t) use ($journal, $repository) {
                $t->before = $repository->getAmountBefore($journal, $t);
                $t->after  = $t->before + $t->amount;
            }
        );


        return view('transactions.show', compact('journal'))->with(
            'subTitle', e($journal->transactiontype->type) . ' "' . e($journal->description) . '"'
        );
    }

    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(JournalFormRequest $request, JournalRepositoryInterface $repository)
    {

        $journalData = $request->getJournalData();
        $journal     = $repository->store($journalData);

        // rescan journal, UpdateJournalConnection
        event(new JournalSaved($journal));
        // ConnectJournalToPiggyBank
        event(new JournalCreated($journal, intval($request->get('piggy_bank_id'))));

        $repository->deactivateReminder($request->get('reminder_id'));

        Session::flash('success', 'New transaction "' . $journal->description . '" stored!');

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('transactions.create.fromStore', true);

            return Redirect::route('transactions.create', $request->input('what'))->withInput();
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('transactions.create.url'));

    }



    /**
     * @param JournalFormRequest         $request
     * @param JournalRepositoryInterface $repository
     * @param TransactionJournal         $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(JournalFormRequest $request, JournalRepositoryInterface $repository, TransactionJournal $journal)
    {

        $journalData = $request->getJournalData();
        $repository->update($journal, $journalData);

        event(new JournalSaved($journal));
        // update, get events by date and sort DESC

        Session::flash('success', 'Transaction "' . e($journalData['description']) . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit.fromUpdate', true);

            return Redirect::route('transactions.edit', $journal->id)->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return Redirect::to(Session::get('transactions.edit.url'));

    }

}
