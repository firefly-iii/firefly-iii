<?php


use Firefly\Exception\FireflyException;
use Firefly\Helper\Controllers\TransactionInterface as TI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;
use Illuminate\Support\MessageBag;

/**
 * Class TransactionController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 *
 */
class TransactionController extends BaseController
{

    protected $_helper;
    protected $_repository;

    /**
     * Construct a new transaction controller with two of the most often used helpers.
     *
     * @param TJRI $repository
     * @param TI $helper
     */
    public function __construct(TJRI $repository, TI $helper)
    {
        $this->_repository = $repository;
        $this->_helper = $helper;
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
        $budgets = $toolkit->makeSelectList($budgetRepository->get());
        $budgets[0] = '(no budget)';

        // get the piggy banks.
        $piggies = $toolkit->makeSelectList($piggyRepository->get());
        $piggies[0] = '(no piggy bank)';

        /*
         * Catch messages from validation round:
         */
        if (Session::has('messages')) {
            $messages = Session::get('messages');
            Session::forget('messages');
        } else {
            $messages = new MessageBag;
        }

        return View::make('transactions.create')->with('accounts', $assetAccounts)->with('budgets', $budgets)->with(
            'what', $what
        )->with('piggies', $piggies)->with('subTitle', 'Add a new ' . $what)->with('messages', $messages);
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
        $budgets = $toolkit->makeSelectList($budgetRepository->get());
        $budgets[0] = '(no budget)';

        /*
         * Get all piggy banks plus (if any) the relevant piggy bank. Since just one
         * of the transactions in the journal has this field, it should all fill in nicely.
         */
        // get the piggy banks.
        $piggies = $toolkit->makeSelectList($piggyRepository->get());
        $piggies[0] = '(no piggy bank)';
        $piggyBankId = 0;
        foreach ($journal->transactions as $t) {
            if (!is_null($t->piggybank_id)) {
                $piggyBankId = $t->piggybank_id;
            }
        }

        /*
         * Data to properly display the edit form.
         */
        $prefilled = [
            'date' => $journal->date->format('Y-m-d'),
            'category' => '',
            'budget_id' => 0,
            'piggybank_id' => $piggyBankId
        ];

        /*
         * Fill in the category.
         */
        $category = $journal->categories()->first();
        if (!is_null($category)) {
            $prefilled['category'] = $category->name;
        }

        /*
         * Switch on the type of transaction edited by the user and fill in other
         * relevant fields:
         */
        switch ($what) {
            case 'withdrawal':
                $prefilled['account_id'] = $journal->transactions[0]->account->id;
                $prefilled['expense_account'] = $journal->transactions[1]->account->name;
                $prefilled['amount'] = floatval($journal->transactions[1]->amount);
                $budget = $journal->budgets()->first();
                if (!is_null($budget)) {
                    $prefilled['budget_id'] = $budget->id;
                }
                break;
            case 'deposit':
                $prefilled['account_id'] = $journal->transactions[1]->account->id;
                $prefilled['revenue_account'] = $journal->transactions[0]->account->name;
                $prefilled['amount'] = floatval($journal->transactions[1]->amount);
                break;
            case 'transfer':
                $prefilled['account_from_id'] = $journal->transactions[1]->account->id;
                $prefilled['account_to_id'] = $journal->transactions[0]->account->id;
                $prefilled['amount'] = floatval($journal->transactions[1]->amount);
                break;
        }

        /*
         * Show the view.
         */
        return View::make('transactions.edit')->with('journal', $journal)->with('accounts', $accounts)->with(
            'what', $what
        )->with('budgets', $budgets)->with('data', $prefilled)->with('piggies', $piggies)->with(
            'subTitle', 'Edit ' . $what . ' "' . $journal->description . '"'
        );
    }

    /**
     * @return $this
     */
    public function expenses()
    {
        return View::make('transactions.list')->with('subTitle', 'Expenses')->with(
            'subTitleIcon', 'fa-long-arrow-left'
        )->with('what', 'expenses');
    }

    /**
     * @return $this
     */
    public function revenue()
    {
        return View::make('transactions.list')->with('subTitle', 'Revenue')->with(
            'subTitleIcon', 'fa-long-arrow-right'
        )->with('what', 'revenue');
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this
     */
    public function show(TransactionJournal $journal)
    {
        return View::make('transactions.show')->with('journal', $journal)->with(
            'subTitle', $journal->transactionType->type . ' "' . $journal->description . '"'
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
        /*
         * Collect data to process:
         */
        $data = Input::except(['_token']);
        $data['what'] = $what;

        switch (Input::get('post_submit_action')) {
            case 'store':
            case 'create_another':
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

                /*
                 * Redirect to original location or back to the form.
                 */
                if (Input::get('post_submit_action') == 'create_another') {
                    return Redirect::route('transactions.create', $what)->withInput();
                } else {
                    return Redirect::route('transactions.index.' . $what);
                }

                break;
            case 'validate_only':
                $messageBags = $this->_helper->validate($data);

                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('transactions.create', [$what])->withInput();
                break;
            default:
                throw new FireflyException('Method ' . Input::get('post_submit_action') . ' not implemented yet.');
                break;
        }
    }

    public function transfers()
    {
        return View::make('transactions.list')->with('subTitle', 'Transfers')->with(
            'subTitleIcon', 'fa-arrows-h'
        )->with('what', 'transfers');

    }

    /**
     * @param TransactionJournal $journal
     *
     * @throws FireflyException
     */
    public function update(TransactionJournal $journal)
    {
        switch (Input::get('post_submit_action')) {
            case 'store':
            case 'return_to_edit':
                $what = strtolower($journal->transactionType->type);
                $messageBag = $this->_helper->update($journal, Input::all());
                if ($messageBag->count() == 0) {
                    // has been saved, return to index:
                    Session::flash('success', 'Transaction updated!');
                    Event::fire('journals.update', [$journal]);

                    if (Input::get('post_submit_action') == 'return_to_edit') {
                        return Redirect::route('transactions.edit', $journal->id);
                    } else {
                        return Redirect::route('transactions.index.' . $what);
                    }
                } else {
                    Session::flash('error', 'Could not update transaction: ' . $journal->errors()->first());

                    return Redirect::route('transactions.edit', $journal->id)->withInput()->withErrors(
                        $journal->errors()
                    );
                }

                break;
            case 'validate_only':
                $data = Input::all();
                $data['what'] = strtolower($journal->transactionType->type);
                $messageBags = $this->_helper->validate($data);

                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);
                return Redirect::route('transactions.edit', $journal->id)->withInput();
                break;
            default:
                throw new FireflyException('Method ' . Input::get('post_submit_action') . ' not implemented yet.');
                break;
        }


    }

} 