<?php

use FireflyIII\Exception\FireflyException;
use Illuminate\Support\MessageBag;

/**
 * Class AccountController
 */
class AccountController extends BaseController
{

    /**
     *
     */
    public function __construct()
    {
        View::share('mainTitleIcon', 'fa-credit-card');
        View::share('title', 'Accounts');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create($what)
    {
        switch ($what) {
            case 'asset':
                $subTitleIcon = 'fa-money';
                break;
            case 'expense':
                $subTitleIcon = 'fa-shopping-cart';
                break;
            case 'revenue':
                $subTitleIcon = 'fa-download';
                break;
        }
        $subTitle = 'Create a new ' . $what . ' account';

        return View::make('accounts.create', compact('subTitleIcon', 'what', 'subTitle'));
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function delete(Account $account)
    {
        $subTitle = 'Delete ' . strtolower($account->accountType->type) . ' "' . $account->name . '"';

        return View::make('accounts.delete', compact('account', 'subTitle'));
    }

    /**
     * @param Account $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Account $account)
    {

        $type = $account->accountType->type;

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\TransactionJournal $jrnls */
        $jrnls = App::make('FireflyIII\Database\TransactionJournal');

        /*
         * Find the "initial balance account", should it exist:
         */
        $initialBalance = $acct->findInitialBalanceAccount($account);

        /*
         * Get all the transaction journals that are part of this/these account(s):
         */
        $journals = [];
        if ($initialBalance) {
            $transactions = $initialBalance->transactions()->get();
            /** @var \Transaction $transaction */
            foreach ($transactions as $transaction) {
                $journals[] = $transaction->transaction_journal_id;
            }
        }
        /** @var \Transaction $transaction */
        foreach ($account->transactions() as $transaction) {
            $journals[] = $transaction->transaction_journal_id;
        }

        $journals = array_unique($journals);

        /*
         * Delete the journals. Should get rid of the transactions as well.
         */
        foreach ($journals as $id) {
            $journal = $jrnls->find($id);
            $jrnls->destroy($journal);
        }

        /*
         * Delete the initial balance as well.
         */
        if ($initialBalance) {
            $acct->destroy($initialBalance);
        }
        $name = $account->name;

        $acct->destroy($account);


        $return = 'asset';
        switch ($type) {
            case 'Expense account':
            case 'Beneficiary account':
                $return = 'expense';
                break;
            case 'Revenue account':
                $return = 'revenue';
                break;
        }

        Session::flash('success', 'The ' . $return . ' account "' . e($name) . '" was deleted.');

        return Redirect::route('accounts.index', $return);


    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function edit(Account $account)
    {
        $prefilled = [];

        switch ($account->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $subTitleIcon              = 'fa-money';
                $prefilled['account_role'] = $account->getMeta('accountRole');
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $subTitleIcon = 'fa-shopping-cart';
                break;
            case 'Revenue account':
                $subTitleIcon = 'fa-download';
                break;
        }

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        $openingBalance = $acct->openingBalanceTransaction($account);
        Session::forget('prefilled');
        if (!is_null($openingBalance)) {
            $prefilled['openingbalancedate'] = $openingBalance->date->format('Y-m-d');
            $prefilled['openingbalance']     = floatval($openingBalance->transactions()->where('account_id', $account->id)->first()->amount);
        }
        Session::flash('prefilled', $prefilled);

        return View::make('accounts.edit', compact('account', 'openingBalance', 'subTitleIcon'))->with(
            'subTitle', 'Edit ' . strtolower(
                $account->accountType->type
            ) . ' "' . $account->name . '"'
        );
    }

    /**
     * @param string $what
     *
     * @return View
     * @throws FireflyException
     */
    public function index($what = 'default')
    {
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        switch ($what) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($what) . '".');
                break;
            case 'asset':
                $subTitleIcon = 'fa-money';
                $subTitle     = 'Asset accounts';
                $accounts     = $acct->getAssetAccounts();
                break;
            case 'expense':
                $subTitleIcon = 'fa-shopping-cart';
                $subTitle     = 'Expense accounts';
                $accounts     = $acct->getExpenseAccounts();
                break;
            case 'revenue':
                $subTitleIcon = 'fa-download';
                $subTitle     = 'Revenue accounts';
                $accounts     = $acct->getRevenueAccounts();
                break;
        }

        $accounts->each(
            function (Account $account) {
                if (Cache::has('account.' . $account->id . '.lastActivityDate')) {
                    $account->lastActionDate = Cache::get('account.' . $account->id . '.lastActivityDate');
                } else {
                    $transaction = $account->transactions()->orderBy('updated_at', 'DESC')->first();
                    if (is_null($transaction)) {
                        $account->lastActionDate = null;
                        Cache::forever('account.' . $account->id . '.lastActivityDate', 0);
                    } else {
                        $account->lastActionDate = $transaction->updated_at;
                        Cache::forever('account.' . $account->id . '.lastActivityDate', $transaction->updated_at);
                    }
                }

            }
        );

        return View::make('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
    }

    /**
     * @param Account $account
     * @param string  $view
     *
     * @return $this
     */
    public function show(Account $account, $view = 'session')
    {
        switch ($account->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $subTitleIcon = 'fa-money';
                $what         = 'asset';
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $subTitleIcon = 'fa-shopping-cart';
                $what         = 'expense';
                break;
            case 'Revenue account':
                $subTitleIcon = 'fa-download';
                $what         = 'revenue';
                break;
        }

        // get a paginated view of all transactions for this account:
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');
        switch ($view) {
            default:
            case 'session':
                $journals = $acct->getTransactionJournals($account, 50);
                break;
            case 'all':
                $journals = $acct->getAllTransactionJournals($account, 50);

                break;
        }


        return View::make('accounts.show', compact('account', 'what', 'view', 'subTitleIcon', 'journals'))->with('account', $account)->with(
            'subTitle', 'Details for ' . strtolower($account->accountType->type) . ' "' . $account->name . '"'
        );
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     * @throws FireflyException
     */
    public function store()
    {

        $data         = Input::all();
        $data['what'] = isset($data['what']) && $data['what'] != '' ? $data['what'] : 'asset';
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        switch ($data['post_submit_action']) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e($data['post_submit_action']) . '"');
                break;
            case 'return_to_edit':
            case 'store':
                $messages = $acct->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save account: ' . $messages['errors']->first());

                    return Redirect::route('accounts.create', $data['what'])->withInput()->withErrors($messages['errors']);
                }
                // store!
                $acct->store($data);
                Session::flash('success', 'New account stored!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('accounts.create', $data['what']);
                } else {
                    return Redirect::route('accounts.index', $data['what']);
                }
                break;
            case 'validate_only':
                $messageBags = $acct->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('accounts.create', $data['what'])->withInput();
                break;
        }
    }

    /**
     * @param Account $account
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(Account $account)
    {

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');
        $data = Input::except('_token');

        switch ($account->accountType->type) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($account->accountType->type) . '"');
                break;
            case 'Default account':
            case 'Asset account':
                $data['what'] = 'asset';
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $data['what'] = 'expense';
                break;
            case 'Revenue account':
                $data['what'] = 'revenue';
                break;
        }

        switch (Input::get('post_submit_action')) {
            default:
                throw new FireflyException('Cannot handle post_submit_action "' . e(Input::get('post_submit_action')) . '"');
                break;
            case 'create_another':
            case 'update':
                $messages = $acct->validate($data);
                /** @var MessageBag $messages ['errors'] */
                if ($messages['errors']->count() > 0) {
                    Session::flash('warnings', $messages['warnings']);
                    Session::flash('successes', $messages['successes']);
                    Session::flash('error', 'Could not save account: ' . $messages['errors']->first());

                    return Redirect::route('accounts.edit', $account->id)->withInput()->withErrors($messages['errors']);
                }
                // store!
                $acct->update($account, $data);
                Session::flash('success', 'Account updated!');

                if ($data['post_submit_action'] == 'create_another') {
                    return Redirect::route('accounts.edit', $account->id);
                } else {
                    return Redirect::route('accounts.index', $data['what']);
                }
            case 'validate_only':
                $messageBags = $acct->validate($data);
                Session::flash('warnings', $messageBags['warnings']);
                Session::flash('successes', $messageBags['successes']);
                Session::flash('errors', $messageBags['errors']);

                return Redirect::route('accounts.edit', $account->id)->withInput();
                break;
        }
    }


}
