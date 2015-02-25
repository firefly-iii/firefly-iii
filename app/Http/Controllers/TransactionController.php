<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use ExpandedForm;
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
        $members = new Collection;
        /** @var TransactionGroup $group */
        foreach ($journal->transactiongroups()->get() as $group) {
            /** @var TransactionJournal $loopJournal */
            foreach ($group->transactionjournals()->get() as $loopJournal) {
                if ($loopJournal->id != $journal->id) {
                    $members->push($loopJournal);
                }
            }
        }

        return view('transactions.show', compact('journal', 'members'))->with('subTitle', e($journal->transactiontype->type) . ' "' . e($journal->description) . '"'
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

        Session::flash('success', 'New transaction "' . $journal->description . '" stored!');

        return Redirect::route('transactions.index', $request->input('what'));

    }

}
