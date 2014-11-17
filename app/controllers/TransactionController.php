<?php


use FireflyIII\Exception\FireflyException;
use Illuminate\Support\MessageBag;

/**
 * Class TransactionController
 *
 */
class TransactionController extends BaseController
{


    /**
     * Construct a new transaction controller with two of the most often used helpers.
     *
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
        /*
         * The repositories we need:
         */
        /** @var \FireflyIII\Shared\Toolkit\Form $form */
        $form = App::make('FireflyIII\Shared\Toolkit\Form');

        /** @var \FireflyIII\Database\Account $accountRepository */
        $accountRepository = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\Budget $budgetRepository */
        $budgetRepository = App::make('FireflyIII\Database\Budget');

        /** @var \FireflyIII\Database\Piggybank $piggyRepository */
        $piggyRepository = App::make('FireflyIII\Database\Piggybank');

        // get asset accounts with names and id's .
        $assetAccounts = $form->makeSelectList($accountRepository->getAssetAccounts());

        // get budgets as a select list.
        $budgets    = $form->makeSelectList($budgetRepository->get());
        $budgets[0] = '(no budget)';

        // get the piggy banks.
        $piggies    = $form->makeSelectList($piggyRepository->get());
        $piggies[0] = '(no piggy bank)';

        /*
         * respond to a possible given values in the URL.
         */
        $prefilled = Session::has('prefilled') ? Session::get('prefilled') : [];
        $respondTo = ['account_id', 'account_from_id'];
        foreach ($respondTo as $r) {
            if (!is_null(Input::get($r))) {
                $prefilled[$r] = Input::get($r);
            }
        }
        Session::put('prefilled', $prefilled);

        return View::make('transactions.create')->with('accounts', $assetAccounts)->with('budgets', $budgets)->with('what', $what)->with('piggies', $piggies)
                   ->with('subTitle', 'Add a new ' . $what);
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

        /*
         * Trigger creation of new piggy bank event
         */
        Event::fire('piggybank.destroyTransfer', [$transactionJournal]); // new and used.

        /** @var \FireflyIII\Database\TransactionJournal $repository */
        $repository = App::make('FireflyIII\Database\TransactionJournal');
        $repository->destroy($transactionJournal);


        switch ($type) {
            case 'Withdrawal':
                return Redirect::route('transactions.index', 'withdrawal');
                break;
            case 'Deposit':
                return Redirect::route('transactions.index', 'deposit');
                break;
            case 'Transfer':
                return Redirect::route('transactions.index', 'transfers');
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
         * TODO the piggybank id must be filled in when relevant.
         */
        /*
         * All the repositories we need:
         */
        /** @var \FireflyIII\Shared\Toolkit\Form $form */
        $form = App::make('FireflyIII\Shared\Toolkit\Form');

        /** @var \FireflyIII\Database\Account $accountRepository */
        $accountRepository = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\Budget $budgetRepository */
        $budgetRepository = App::make('FireflyIII\Database\Budget');

        /** @var \FireflyIII\Database\Piggybank $piggyRepository */
        $piggyRepository = App::make('FireflyIII\Database\Piggybank');


        // type is useful for display:
        $what = strtolower($journal->transactiontype->type);

        // get asset accounts with names and id's.
        $accounts = $form->makeSelectList($accountRepository->getAssetAccounts());

        // get budgets as a select list.
        $budgets    = $form->makeSelectList($budgetRepository->get());
        $budgets[0] = '(no budget)';

        /*
         * Get all piggy banks plus (if any) the relevant piggy bank. Since just one
         * of the transactions in the journal has this field, it should all fill in nicely.
         */
        // get the piggy banks.
        $piggies     = $form->makeSelectList($piggyRepository->get());
        $piggies[0]  = '(no piggy bank)';
        $piggyBankId = 0;
        foreach ($journal->transactions as $t) {
            if (!is_null($t->piggybank_id)) {
                $piggyBankId = $t->piggybank_id;
            }
        }

        /*
         * Data to properly display the edit form.
         */
        $prefilled = ['date' => $journal->date->format('Y-m-d'), 'category' => '', 'budget_id' => 0, 'piggybank_id' => $piggyBankId];

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
                $prefilled['account_id']      = $journal->transactions[0]->account->id;
                $prefilled['expense_account'] = $journal->transactions[1]->account->name;
                $prefilled['amount']          = floatval($journal->transactions[1]->amount);
                $budget                       = $journal->budgets()->first();
                if (!is_null($budget)) {
                    $prefilled['budget_id'] = $budget->id;
                }
                break;
            case 'deposit':
                $prefilled['account_id']      = $journal->transactions[1]->account->id;
                $prefilled['revenue_account'] = $journal->transactions[0]->account->name;
                $prefilled['amount']          = floatval($journal->transactions[1]->amount);
                break;
            case 'transfer':
                if (floatval($journal->transactions[0]->amount) < 0) {
                    // zero = from account.
                    $prefilled['account_from_id'] = $journal->transactions[0]->account->id;
                    $prefilled['account_to_id']   = $journal->transactions[1]->account->id;
                    $prefilled['amount']          = floatval($journal->transactions[1]->amount);
                } else {
                    // one = from account
                    $prefilled['account_from_id'] = $journal->transactions[1]->account->id;
                    $prefilled['account_to_id']   = $journal->transactions[0]->account->id;
                    $prefilled['amount']          = floatval($journal->transactions[0]->amount);
                }
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
     * @param $what
     *
     * @return $this
     */
    public function index($what)
    {

        /** @var \FireflyIII\Database\TransactionJournal $repository */
        $repository = App::make('FireflyIII\Database\TransactionJournal');

        switch ($what) {
            case 'expenses':
            case 'withdrawal':
                $subTitleIcon = 'fa-long-arrow-left';
                $subTitle     = 'Expenses';
                $journals     = $repository->getWithdrawalsPaginated(50);
                break;
            case 'revenue':
            case 'deposit':
                $subTitleIcon = 'fa-long-arrow-right';
                $subTitle     = 'Revenue, income and deposits';
                $journals     = $repository->getDepositsPaginated(50);
                break;
            case 'transfer':
            case 'transfers':
                $subTitleIcon = 'fa-arrows-h';
                $subTitle     = 'Transfers';
                $journals     = $repository->getTransfersPaginated(50);
                break;
        }

        return View::make('transactions.index', compact('subTitle', 'subTitleIcon', 'journals'))->with('what', $what);

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
        $data             = Input::except('_token');
        $data['what']     = $what;
        $data['currency'] = 'EUR';

        /** @var \FireflyIII\Database\TransactionJournal $repository */
        $repository = App::make('FireflyIII\Database\TransactionJournal');

        switch ($data['post_submit_action']) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e($data['post_submit_action']) . '"');
                break;
            case 'create_another':
            case 'store':
                $messages = $repository->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save transaction: ' . $messages['errors']->first());

                    return Redirect::route('transactions.create', $what)->withInput()->withErrors($messages['errors']);
                }
                // store!
                $journal = $repository->store($data);
                Session::flash('success', 'New transaction stored!');

                /*
                 * Trigger a search for the related (if selected)
                 * piggy bank and store an event.
                 */
                if (!is_null(Input::get('piggybank_id')) && intval(Input::get('piggybank_id')) > 0) {
                    Event::fire('piggybank.storeTransfer', [$journal, intval(Input::get('piggybank_id'))]); // new and used.
                }

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('transactions.create', $what)->withInput();
                } else {
                    return Redirect::route('transactions.index', $what);
                }
                break;
            case 'validate_only':
                $messageBags = $repository->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('transactions.create', $what)->withInput();
                break;
        }
    }


    /**
     * @param TransactionJournal $journal
     *
     * @throws FireflyException
     */
    public function update(TransactionJournal $journal)
    {
        /** @var \FireflyIII\Database\TransactionJournal $repos */
        $repos = App::make('FireflyIII\Database\TransactionJournal');

        $data             = Input::except('_token');
        $data['currency'] = 'EUR';
        $data['what']     = strtolower($journal->transactionType->type);


        switch (Input::get('post_submit_action')) {
            case 'update':
            case 'return_to_edit':
                $messageBag = $repos->update($journal, $data);
                if ($messageBag->count() == 0) {
                    // has been saved, return to index:
                    Session::flash('success', 'Transaction updated!');
                    Event::fire('piggybank.updateTransfer', [$journal]); // new and used.

                    if (Input::get('post_submit_action') == 'return_to_edit') {
                        return Redirect::route('transactions.edit', $journal->id)->withInput();
                    } else {
                        return Redirect::route('transactions.index', $data['what']);
                    }
                } else {
                    Session::flash('error', 'Could not update transaction: ' . $journal->errors()->first());

                    return Redirect::route('transactions.edit', $journal->id)->withInput()->withErrors(
                        $journal->errors()
                    );
                }

                break;
            case 'validate_only':
                $messageBags = $repos->validate($data);

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