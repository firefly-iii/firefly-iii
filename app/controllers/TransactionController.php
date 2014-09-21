<?php


use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Firefly\Helper\Controllers\TransactionInterface as TI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class TransactionController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 */
class TransactionController extends BaseController
{

    protected $_repository;

    protected $_helper;

    /** @var Carbon|null $_start */
    protected $_start;

    /** @var Carbon|null $_end */
    protected $_end;

    /**
     * Construct a new transaction controller with two of the most often used helpers.
     *
     * @param TJRI $repository
     * @param TI   $helper
     */
    public function __construct(TJRI $repository, TI $helper)
    {
        $this->_repository = $repository;
        $this->_helper     = $helper;
        View::share('title', 'Transactions');
        View::share('mainTitleIcon', 'fa-repeat');

        /*
         * With this construction, every method has access to a possibly set start
         * and end date, to be used at their leisure:
         */
        $this->_start = is_null(Input::get('startdate')) ? null : new Carbon(Input::get('startdate'));
        $this->_end   = is_null(Input::get('enddate')) ? null : new Carbon(Input::get('enddate'));
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
        /*
         * The repositories we need:
         */
        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');

        /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggyRepository */
        $piggyRepository = App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

        // get asset accounts with names and id's.
        $assetAccounts = $toolkit->makeSelectList($accountRepository->getActiveDefault());

        // get budgets as a select list.
        $budgets    = $toolkit->makeSelectList($budgetRepository->get());
        $budgets[0] = '(no budget)';

        // get the piggy banks.
        $piggies    = $toolkit->makeSelectList($piggyRepository->get());
        $piggies[0] = '(no piggy bank)';

        return View::make('transactions.create')->with('accounts', $assetAccounts)->with('budgets', $budgets)->with(
            'what', $what
        )->with('piggies', $piggies)->with('subTitle', 'Add a new ' . $what);
    }

    /**
     * Shows the form that allows a user to delete a transaction journal.
     *
     * @param TransactionJournal $transactionJournal
     *
     * @return $this
     */
    public function delete(TransactionJournal $transactionJournal)
    {
        $type = strtolower($transactionJournal->transactionType->type);

        return View::make('transactions.delete')->with('journal', $transactionJournal)->with(
            'subTitle', 'Delete ' . $type . ' "' . $transactionJournal->description . '"'
        );


    }


    /**
     * @param TransactionJournal $transactionJournal
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(TransactionJournal $transactionJournal)
    {
        $type = $transactionJournal->transactionType->type;
        $transactionJournal->delete();

        switch ($type) {
            case 'Withdrawal':
                return Redirect::route('transactions.expenses');
                break;
            case 'Deposit':
                return Redirect::route('transactions.revenue');
                break;
            case 'Transfer':
                return Redirect::route('transactions.transfers');
                break;
        }
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
        /*
         * All the repositories we need:
         */
        /** @var \Firefly\Helper\Toolkit\Toolkit $toolkit */
        $toolkit = App::make('Firefly\Helper\Toolkit\Toolkit');

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');

        /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggyRepository */
        $piggyRepository = App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

        // type is useful for display:
        $what = strtolower($journal->transactiontype->type);

        // get asset accounts with names and id's.
        $accounts = $toolkit->makeSelectList($accountRepository->getActiveDefault());

        // get budgets as a select list.
        $budgets    = $budgetRepository->getAsSelectList();
        $budgets[0] = '(no budget)';

        /*
         * Get all piggy banks plus (if any) the relevant piggy bank. Since just one
         * of the transactions in the journal has this field, it should all fill in nicely.
         */
        $piggies     = $piggyRepository->get();
        $piggyBankId = null;
        foreach ($journal->transactions as $t) {
            $piggyBankId = $t->piggybank_id;
        }

        /*
         * Data to properly display the edit form.
         */
        $data = [
            'date'         => $journal->date->format('Y-m-d'),
            'category'     => '',
            'budget_id'    => 0,
            'piggybank_id' => $piggyBankId
        ];

        /*
         * Fill in the category.
         */
        $category = $journal->categories()->first();
        if (!is_null($category)) {
            $data['category'] = $category->name;
        }

        /*
         * Switch on the type of transaction edited by the user and fill in other
         * relevant fields:
         */
        switch ($what) {
            case 'Withdrawal':
                $data['account_id']  = $journal->transactions[0]->account->id;
                $data['beneficiary'] = $journal->transactions[1]->account->name;
                $data['amount']      = floatval($journal->transactions[1]->amount);
                $budget              = $journal->budgets()->first();
                if (!is_null($budget)) {
                    $data['budget_id'] = $budget->id;
                }
                break;
            case 'Deposit':
                $data['account_id']  = $journal->transactions[1]->account->id;
                $data['beneficiary'] = $journal->transactions[0]->account->name;
                $data['amount']      = floatval($journal->transactions[1]->amount);
                break;
            case 'Transfer':
                $data['account_from_id'] = $journal->transactions[1]->account->id;
                $data['account_to_id']   = $journal->transactions[0]->account->id;
                $data['amount']          = floatval($journal->transactions[1]->amount);
                break;
        }

        /*
         * Show the view.
         */
        return View::make('transactions.edit')->with('journal', $journal)->with('accounts', $accounts)->with(
            'what', $what
        )->with('budgets', $budgets)->with('data', $data)->with('piggies', $piggies)->with(
            'subTitle', 'Edit ' . $what . ' "' . $journal->description . '"'
        );
    }

    /**
     * @return $this
     */
    public function expenses()
    {
        #$transactionType = $this->_repository->getTransactionType('Withdrawal');
        #$journals        = $this->_repository->paginate($transactionType, 25, $this->_start, $this->_end);

        return View::make('transactions.list')->with('subTitle', 'Expenses')->with(
            'subTitleIcon', 'fa-long-arrow-left'
        );
    }

    public function revenue()
    {
        $transactionType = $this->_repository->getTransactionType('Deposit');
        $start           = is_null(Input::get('startdate')) ? null : new Carbon(Input::get('startdate'));
        $end             = is_null(Input::get('enddate')) ? null : new Carbon(Input::get('enddate'));
        if ($start <= $end && !is_null($start) && !is_null($end)) {
            $journals = $this->_repository->paginate($transactionType, 25, $start, $end);
            $filtered = true;
            $filters  = ['start' => $start, 'end' => $end];
        } else {
            $journals = $this->_repository->paginate($transactionType, 25);
            $filtered = false;
            $filters  = null;
        }

        View::share('subTitleIcon', 'fa-long-arrow-right');
        return View::make('transactions.index')->with('journals', $journals)->with('filtered', $filtered)->with(
            'filters', $filters
        )->with('subTitle', 'Revenue');

    }

    public function transfers()
    {
        $transactionType = $this->_repository->getTransactionType('Transfer');
        $start           = is_null(Input::get('startdate')) ? null : new Carbon(Input::get('startdate'));
        $end             = is_null(Input::get('enddate')) ? null : new Carbon(Input::get('enddate'));
        if ($start <= $end && !is_null($start) && !is_null($end)) {
            $journals = $this->_repository->paginate($transactionType, 25, $start, $end);
            $filtered = true;
            $filters  = ['start' => $start, 'end' => $end];
        } else {
            $journals = $this->_repository->paginate($transactionType, 25);
            $filtered = false;
            $filters  = null;
        }

        View::share('subTitleIcon', 'fa-arrows-h');
        return View::make('transactions.index')->with('journals', $journals)->with('filtered', $filtered)->with(
            'filters', $filters
        )->with('subTitle', 'Transfers');

    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        $start = is_null(Input::get('startdate')) ? null : new Carbon(Input::get('startdate'));
        $end   = is_null(Input::get('enddate')) ? null : new Carbon(Input::get('enddate'));
        if ($start <= $end && !is_null($start) && !is_null($end)) {
            $journals = $this->_repository->paginate(25, $start, $end);
            $filtered = true;
            $filters  = ['start' => $start, 'end' => $end];
        } else {
            $journals = $this->_repository->paginate(25);
            $filtered = false;
            $filters  = null;
        }


        return View::make('transactions.index')->with('journals', $journals)->with('filtered', $filtered)->with(
            'filters', $filters
        );
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function show(TransactionJournal $journal)
    {
        View::share('subTitle', $journal->transactionType->type . ' "' . $journal->description . '"');

        return View::make('transactions.show')->with('journal', $journal);
    }

    /**
     * @param $what
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     * @throws FireflyException
     */
    public function store($what)
    {
        /*
         * Collect data to process:
         */
        $data         = Input::except(['_token']);
        $data['what'] = $what;

        switch (Input::get('post_submit_action')) {
            case 'store':
                /*
                 * Try to store:
                 */
                $messageBag = $this->_helper->store($data);

                /*
                 * Failure!
                 */
                if ($messageBag->count() > 0) {
                    Session::flash('error', 'Could not save transaction: ' . $messageBag->first());
                    return Redirect::route('transactions.create', [$what])->withInput()->withErrors($messageBag);
                }

                /*
                 * Success!
                 */
                Session::flash('success', 'Transaction "' . e(Input::get('description')) . '" saved!');

                switch ($what) {
                    case 'withdrawal':
                        return Redirect::route('transactions.expenses');
                        break;
                    case 'deposit':
                        return Redirect::route('transactions.revenue');
                        break;
                    case 'transfer':
                        return Redirect::route('transactions.transfers');
                        break;
                }

                break;
            default:
                throw new FireflyException('Method ' . Input::get('post_submit_action') . ' not implemented yet.');
                break;
        }
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(TransactionJournal $journal)
    {
        $journal = $this->_repository->update($journal, Input::all());
        if ($journal->validate()) {
            // has been saved, return to index:
            Session::flash('success', 'Transaction updated!');
            Event::fire('journals.update', [$journal]);

            return Redirect::route('transactions.index');
        } else {
            Session::flash('error', 'Could not update transaction: ' . $journal->errors()->first());

            return Redirect::route('transactions.edit', $journal->id)->withInput()->withErrors($journal->errors());
        }


    }

} 