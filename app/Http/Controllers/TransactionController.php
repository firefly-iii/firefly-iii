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
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Input;
use Redirect;
use Session;
use View;


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
        View::share('title', 'Transactions');
        View::share('mainTitleIcon', 'fa-repeat');
    }

    /**
     * Shows the view helping the user to create a new transaction journal.
     *
     * @param string $what
     *
     * @return \Illuminate\View\View
     */
    public function create($what = 'deposit')
    {
        $accounts   = ExpandedForm::makeSelectList(
            Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->where('active', 1)->orderBy('name', 'DESC')->get(['accounts.*'])
        );
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

        return View::make('transactions.delete', compact('journal', 'subTitle'));


    }

    /**
     * @param TransactionJournal $transactionJournal
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TransactionJournal $transactionJournal)
    {
        $type   = $transactionJournal->transactionType->type;
        $return = 'withdrawal';

        Session::flash('success', 'Transaction "' . e($transactionJournal->description) . '" destroyed.');

        $transactionJournal->delete();

        switch ($type) {
            case 'Deposit':
                $return = 'deposit';
                break;
            case 'Transfer':
                $return = 'transfers';
                break;
        }

        return Redirect::route('transactions.index', $return);
    }

    /**
     * Shows the view to edit a transaction.
     *
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function edit(TransactionJournal $journal, JournalRepositoryInterface $repository)
    {
        $what         = strtolower($journal->transactiontype->type);
        $accounts     = ExpandedForm::makeSelectList(
            Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->where('active', 1)->orderBy('name', 'DESC')->get(['accounts.*'])
        );
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

        $preFilled['amount'] = 0;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            if (floatval($t->amount) > 0) {
                $preFilled['amount'] = floatval($t->amount);
            }
        }
        $preFilled['account_id']      = $repository->getAssetAccount($journal);
        $preFilled['expense_account'] = $transactions[0]->account->name;
        $preFilled['revenue_account'] = $transactions[1]->account->name;
        $preFilled['account_from_id'] = $transactions[1]->account->id;
        $preFilled['account_to_id']   = $transactions[0]->account->id;


        return View::make('transactions.edit', compact('journal', 'accounts', 'what', 'budgets', 'piggies', 'subTitle'))->with('data', $preFilled);
    }

    /**
     * @param $what
     *
     * @return $this
     */
    public function index($what)
    {
        switch ($what) {
            case 'expenses':
            case 'withdrawal':
                $subTitleIcon = 'fa-long-arrow-left';
                $subTitle     = 'Expenses';
                //$journals     = $this->_repository->getWithdrawalsPaginated(50);
                $types = ['Withdrawal'];
                break;
            case 'revenue':
            case 'deposit':
                $subTitleIcon = 'fa-long-arrow-right';
                $subTitle     = 'Revenue, income and deposits';
                //                $journals     = $this->_repository->getDepositsPaginated(50);
                $types = ['Deposit'];
                break;
            case 'transfer':
            case 'transfers':
                $subTitleIcon = 'fa-arrows-h';
                $subTitle     = 'Transfers';
                //$journals     = $this->_repository->getTransfersPaginated(50);
                $types = ['Transfer'];
                break;
        }

        $page   = intval(\Input::get('page'));
        $offset = $page > 0 ? ($page - 1) * 50 : 0;

        $set      = Auth::user()->transactionJournals()->transactionTypes($types)->withRelevantData()->take(50)->offset($offset)->orderBy('date', 'DESC')->get(
            ['transaction_journals.*']
        );
        $count    = Auth::user()->transactionJournals()->transactionTypes($types)->count();
        $journals = new LengthAwarePaginator($set, $count, 50, $page);
        $journals->setPath('transactions/' . $what);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function show(TransactionJournal $journal)
    {
        $journal->transactions->each(
            function (Transaction $t) use ($journal) {
                $t->before = floatval(
                    $t->account->transactions()->leftJoin(
                        'transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id'
                    )->where('transaction_journals.date', '<=', $journal->date->format('Y-m-d'))->where(
                        'transaction_journals.created_at', '<=', $journal->created_at->format('Y-m-d H:i:s')
                    )->where('transaction_journals.id', '!=', $journal->id)->sum('transactions.amount')
                );
                $t->after  = $t->before + $t->amount;
            }
        );


        return view('transactions.show', compact('journal'))->with(
            'subTitle', e($journal->transactiontype->type) . ' "' . e($journal->description) . '"'
        );
    }

    public function store(JournalFormRequest $request, JournalRepositoryInterface $repository)
    {

        $journalData = [
            'what'               => $request->get('what'),
            'description'        => $request->get('description'),
            'account_id'         => intval($request->get('account_id')),
            'account_from_id'    => intval($request->get('account_from_id')),
            'account_to_id'      => intval($request->get('account_to_id')),
            'expense_account'    => $request->get('expense_account'),
            'revenue_account'    => $request->get('revenue_account'),
            'amount'             => floatval($request->get('amount')),
            'user'               => Auth::user()->id,
            'amount_currency_id' => intval($request->get('amount_currency_id')),
            'date'               => new Carbon($request->get('date')),
            'budget_id'          => intval($request->get('budget_id')),
            'category'           => $request->get('category'),
        ];

        $journal = $repository->store($journalData);

        event(new JournalSaved($journal));
        event(new JournalCreated($journal, intval($request->get('piggy_bank_id'))));

        Session::flash('success', 'New transaction "' . $journal->description . '" stored!');

        if (intval(Input::get('create_another')) === 1) {
            return Redirect::route('transactions.create', $request->input('what'))->withInput();
        }

        return Redirect::route('transactions.index', $request->input('what'));

    }

    /**
     * @param TransactionJournal $journal
     *
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(TransactionJournal $journal, JournalFormRequest $request, JournalRepositoryInterface $repository)
    {


        $journalData = [
            'what'               => $request->get('what'),
            'description'        => $request->get('description'),
            'account_id'         => intval($request->get('account_id')),
            'account_from_id'    => intval($request->get('account_from_id')),
            'account_to_id'      => intval($request->get('account_to_id')),
            'expense_account'    => $request->get('expense_account'),
            'revenue_account'    => $request->get('revenue_account'),
            'amount'             => floatval($request->get('amount')),
            'user'               => Auth::user()->id,
            'amount_currency_id' => intval($request->get('amount_currency_id')),
            'date'               => new Carbon($request->get('date')),
            'budget_id'          => intval($request->get('budget_id')),
            'category'           => $request->get('category'),
        ];

        $repository->update($journal, $journalData);

        event(new JournalSaved($journal));
        // update, get events by date and sort DESC

        Session::flash('success', 'Transaction "' . e($journalData['description']) . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            return Redirect::route('transactions.edit', $journal->id);
        }

        return Redirect::route('transactions.index', $journalData['what']);

    }

}
