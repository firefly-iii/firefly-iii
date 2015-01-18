<?php


use FireflyIII\Database\TransactionJournal\TransactionJournal as Repository;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Helper\TransactionJournal\HelperInterface as Helper;
use Illuminate\Support\Collection;

/**
 *
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 *
 * Class TransactionController
 *
 */
class TransactionController extends BaseController
{


    /** @var Helper */
    protected $_helper;
    /** @var Repository */
    protected $_repository;

    /**
     * Construct a new transaction controller with two of the most often used helpers.
     *
     * @param Repository $repository
     * @param Helper     $helper
     */
    public function __construct(Repository $repository, Helper $helper)
    {
        $this->_repository = $repository;
        $this->_helper     = $helper;
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
        $accounts         = FFForm::makeSelectList($this->_helper->getAssetAccounts());
        $budgets          = FFForm::makeSelectList($this->_helper->getBudgets());
        $budgets[0]       = '(no budget)';
        $piggyBanks       = $this->_helper->getPiggyBanks();
        $repeatedExpenses = $this->_helper->getRepeatedExpenses();
        $list             = $piggyBanks->merge($repeatedExpenses);
        $piggies          = FFForm::makeSelectList($list);
        $piggies[0]       = '(no piggy bank)';
        $preFilled        = Session::has('preFilled') ? Session::get('preFilled') : [];
        $respondTo        = ['account_id', 'account_from_id'];
        $subTitle         = 'Add a new ' . e($what);

        foreach ($respondTo as $r) {
            if (!is_null(Input::get($r))) {
                $preFilled[$r] = Input::get($r);
            }
        }
        Session::put('preFilled', $preFilled);

        asort($piggies);


        return View::make('transactions.create', compact('accounts', 'budgets', 'what', 'piggies', 'subTitle'));
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

        $this->_repository->destroy($transactionJournal);

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
    public function edit(TransactionJournal $journal)
    {
        $what         = strtolower($journal->transactiontype->type);
        $subTitle     = 'Edit ' . e($what) . ' "' . e($journal->description) . '"';
        $budgets      = FFForm::makeSelectList($this->_helper->getBudgets(), true);
        $accounts     = FFForm::makeSelectList($this->_helper->getAssetAccounts());
        $piggies      = FFForm::makeSelectList($this->_helper->getPiggyBanks(), true);
        $transactions = $journal->transactions()->orderBy('amount', 'DESC')->get();
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
            $preFilled['piggy_bank_id'] = $journal->piggyBankEvents()->first()->piggy_bank_id;
        }

        $preFilled['amount']          = $journal->getAmount();
        $preFilled['account_id']      = $this->_helper->getAssetAccount($what, $transactions);
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
                $journals     = $this->_repository->getWithdrawalsPaginated(50);
                break;
            case 'revenue':
            case 'deposit':
                $subTitleIcon = 'fa-long-arrow-right';
                $subTitle     = 'Revenue, income and deposits';
                $journals     = $this->_repository->getDepositsPaginated(50);
                break;
            case 'transfer':
            case 'transfers':
                $subTitleIcon = 'fa-arrows-h';
                $subTitle     = 'Transfers';
                $journals     = $this->_repository->getTransfersPaginated(50);
                break;
        }

        return View::make('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'journals'));

    }


    /**
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function show(TransactionJournal $journal)
    {
        $journal->transactions->each(
            function (\Transaction $t) use ($journal) {
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

        return View::make('transactions.show', compact('journal', 'members'))->with(
            'subTitle', e($journal->transactionType->type) . ' "' . e($journal->description) . '"'
        );
    }

    /**
     * @param $what
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     * @throws FireflyException
     */
    public function store($what)
    {
        $data                            = Input::except('_token');
        $transactionType                 = $this->_repository->getJournalType($what);
        $transactionCurrency             = $this->_repository->getJournalCurrency('EUR');
        $data['transaction_type_id']     = $transactionType->id;
        $data['transaction_currency_id'] = $transactionCurrency->id;
        $data['completed']               = 0;
        $data['what']                    = $what;
        $data['currency']                = 'EUR';

        // always validate:
        $messages = $this->_repository->validate($data);

        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store transaction: ' . $messages['errors']->first());
            return Redirect::route('transactions.create', $data['what'])->withInput();
        }

        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only') {
            return Redirect::route('transactions.create', $data['what'])->withInput();
        }

        // store
        $journal = $this->_repository->store($data);
        Event::fire('transactionJournal.store', [$journal, Input::get('piggy_bank_id')]); // new and used.
        /*
         * Also trigger on both transactions.
         */
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            Event::fire('transaction.store', [$transaction]);
        }

        Session::flash('success', 'Transaction "' . e($data['description']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('transactions.index', $data['what']);
        }

        return Redirect::route('transactions.create', $data['what'])->withInput();
    }


    /**
     * @param TransactionJournal $journal
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(TransactionJournal $journal)
    {
        $data                            = Input::except('_token');
        $data['currency']                = 'EUR';
        $data['what']                    = strtolower($journal->transactionType->type);
        $data['transaction_type_id']     = $journal->transaction_type_id;
        $data['transaction_currency_id'] = $journal->transaction_currency_id;
        $data['completed']               = 1;
        $messages                        = $this->_repository->validate($data);

        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update transaction: ' . $messages['errors']->first());
        }
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('transactions.edit', $journal->id)->withInput();
        }
        $this->_repository->update($journal, $data);
        Session::flash('success', 'Transaction "' . e($data['description']) . '" updated.');
        Event::fire('transactionJournal.update', [$journal]); // new and used.
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            Event::fire('transaction.update', [$transaction]);
        }
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('transactions.index', $data['what']);
        }

        // go back to update screen.
        return Redirect::route('transactions.edit', $journal->id)->withInput(['post_submit_action' => 'return_to_edit']);


    }

}
