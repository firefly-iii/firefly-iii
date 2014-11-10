<?php

use Firefly\Exception\FireflyException;
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
     * @param string $what
     *
     * @return View
     * @throws FireflyException
     */
    public function index($what = 'default')
    {
        switch ($what) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($what) . '".');
                break;
            case 'asset':
                $subTitleIcon = 'fa-money';
                $subTitle = 'Asset accounts';
                break;
            case 'expense':
                $subTitleIcon = 'fa-shopping-cart';
                $subTitle = 'Expense accounts';
                break;
            case 'revenue':
                $subTitleIcon = 'fa-download';
                $subTitle = 'Revenue accounts';
                break;
        }
        return View::make('accounts.index')
	        ->with('what', $what)
	        ->with(compact('subTitleIcon'))
	        ->with(compact('subTitle'));
    }


    /**
     * @param string $what
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws FireflyException
     */
    public function json($what = 'default')
    {
        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Shared\Json\Json $json */
        $json = App::make('FireflyIII\Shared\Json\Json');

        $parameters = $json->dataTableParameters();

        switch ($what) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($what) . '".');
                break;
            case 'asset':
                $accounts = $acct->getAssetAccounts($parameters);
                $count    = $acct->countAssetAccounts();
                break;
            case 'expense':
                $accounts = $acct->getExpenseAccounts($parameters);
                $count    = $acct->countExpenseAccounts();
                break;
            case 'revenue':
                $accounts = $acct->getRevenueAccounts($parameters);
                $count    = $acct->countRevenueAccounts();
                break;
        }

        /*
         * Output the set compatible with data tables:
         */
        $return = [
            'draw'            => intval(Input::get('draw')),
            'recordsTotal'    => $count,
            'recordsFiltered' => $accounts->count(),
            'data'            => [],
        ];

        /*
         * Loop the accounts:
         */
        /** @var \Account $account */
        foreach ($accounts as $account) {
            $entry            = [
                'name'    => ['name' => $account->name, 'url' => route('accounts.show', $account->id)],
                'balance' => $account->balance(),
                'id'      => [
                    'edit'   => route('accounts.edit', $account->id),
                    'delete' => route('accounts.delete', $account->id),
                ]
            ];
            $return['data'][] = $entry;
        }


        return Response::jsoN($return);
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

        return View::make('accounts.create')
	        ->with('subTitle', 'Create a new ' . $what . ' account')
	        ->with('what', $what)
	        ->with(compact('subTitleIcon'));
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function delete(Account $account)
    {
        return View::make('accounts.delete')->with('account', $account)
                   ->with(
                       'subTitle', 'Delete ' . strtolower($account->accountType->type) . ' "' . $account->name . '"'
                   );
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
            $journal->delete();
        }

        /*
         * Delete it
         */
        if ($initialBalance) {
            $acct->destroy($initialBalance);
        }

        $acct->destroy($account);

        Session::flash('success', 'The account was deleted.');
        switch ($type) {
            case 'Asset account':
            case 'Default account':
                return Redirect::route('accounts.index', 'asset');
                break;
            case 'Expense account':
            case 'Beneficiary account':
                return Redirect::route('accounts.index', 'expense');
                break;
            case 'Revenue account':
                return Redirect::route('accounts.index', 'revenue');
                break;
        }


    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function edit(Account $account)
    {

        switch ($account->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $subTitleIcon = 'fa-money';
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
            Session::flash('prefilled', $prefilled);
        }

        return View::make('accounts.edit')
	        ->with('account', $account)
	        ->with('openingBalance', $openingBalance)
            ->with(compact('subTitleIcon'))
	        ->with('subTitle', 'Edit ' . strtolower(
                $account->accountType->type
            ) . ' "' . $account->name . '"'
        );
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function show(Account $account)
    {
        switch ($account->accountType->type) {
            case 'Asset account':
            case 'Default account':
                $subTitleIcon = 'fa-money';
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $subTitleIcon = 'fa-shopping-cart';
                break;
            case 'Revenue account':
                $subTitleIcon = 'fa-download';
                break;
        }


        //$data = $this->_accounts->show($account, 40);
        return View::make('accounts.show')
            ->with('account', $account)
            ->with('subTitle', 'Details for ' . strtolower($account->accountType->type) . ' "' . $account->name . '"')
	        ->with(compact('subTitleIcon'));
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
                $data['what'] = 'asset';
                break;
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
                    return Redirect::route('accounts.index',$data['what']);
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
