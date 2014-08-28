<?php

use Firefly\Helper\Controllers\AccountInterface as AI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

/**
 * Class AccountController
 */
class AccountController extends \BaseController
{

    protected $_repository;
    protected $_accounts;

    /**
     * @param ARI $repository
     * @param AI  $accounts
     */
    public function __construct(ARI $repository, AI $accounts)
    {
        $this->_accounts = $accounts;
        $this->_repository = $repository;
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return View::make('accounts.create');
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function delete(Account $account)
    {
        $accountType = $account->accountType()->first();

        if ($accountType->description == 'Initial balance account' || $accountType->description == 'Cash account') {
            return \View::make('error')->with(
                'message', 'Cannot edit this account type (' . $accountType->description . ').'
            );
        }

        return View::make('accounts.delete')->with('account', $account);
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Account $account)
    {
        $accountType = $account->accountType()->first();

        if ($accountType->description == 'Initial balance account' || $accountType->description == 'Cash account') {
            return View::make('error')->with(
                'message', 'Cannot edit this account type (' . $accountType->description . ').'
            );
        }
        $result = $this->_repository->destroy($account);
        if ($result === true) {
            Session::flash('success', 'The account was deleted.');
        } else {
            Session::flash('error', 'Could not delete the account.');
        }

        return Redirect::route('accounts.index');

    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function edit(Account $account)
    {
        $accountType = $account->accountType()->first();

        if ($accountType->description == 'Initial balance account' || $accountType->description == 'Cash account') {
            return View::make('error')->with(
                'message', 'Cannot edit this account type (' . $accountType->description . ').'
            );
        }
        $openingBalance = $this->_accounts->openingBalanceTransaction($account);

        return View::make('accounts.edit')->with('account', $account)->with('openingBalance', $openingBalance);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $accounts = $this->_repository->get();
        $display = $this->_accounts->index($accounts);

        return View::make('accounts.index')->with('accounts', $display);
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function show(Account $account)
    {
        $accountType = $account->accountType()->first();
        if ($accountType->description == 'Initial balance account' || $accountType->description == 'Cash account') {
            return View::make('error')->with(
                'message', 'Cannot show this account type (' . $accountType->description . ').'
            );
        }

        $show = $this->_accounts->show($account, 40);

        return View::make('accounts.show')->with('account', $account)->with('show', $show);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {

        $account = $this->_repository->store(Input::all());

        if ($account->validate()) {
            // saved! return to wherever.
            Session::flash('success', 'Account "' . $account->name . '" created!');
            if (Input::get('create') == '1') {
                return Redirect::route('accounts.create')->withInput();
            } else {
                return Redirect::route('accounts.index');
            }
        } else {
            // did not save, return with error:
            Session::flash('error', 'Could not save the new account. Please check the form.');

            return Redirect::route('accounts.create')->withErrors($account->errors())->withInput();

        }
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Account $account)
    {
        $accountType = $account->accountType()->first();
        if ($accountType->description == 'Initial balance account' || $accountType->description == 'Cash account') {
            return View::make('error')->with(
                'message', 'Cannot show this account type (' . $accountType->description . ').'
            );
        }
        $account = $this->_repository->update($account, Input::all());
        if ($account->validate()) {
            Session::flash('success', 'Account "' . $account->name . '" updated.');

            return Redirect::route('accounts.index');
        } else {
            Session::flash('error', 'Could not update account: ' . $account->errors()->first());

            return Redirect::route('accounts.edit', $account->id)->withInput()->withErrors($account->errors());
        }
    }


}
