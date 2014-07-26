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
        View::share('menu', 'accounts');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
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
        return View::make('accounts.delete')->with('account', $account);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy()
    {
        $result = $this->_repository->destroy(Input::get('id'));
        if ($result === true) {
            Session::flash('success', 'The account was deleted.');

            return Redirect::route('accounts.index');
        } else {
            Session::flash('danger', 'Could not delete the account. Check the logs to be sure.');

            return Redirect::route('accounts.index');
        }

    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function edit(Account $account)
    {
        $openingBalance = $this->_accounts->openingBalanceTransaction($account);

        return View::make('accounts.edit')->with('account', $account)->with('openingBalance', $openingBalance);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
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
        return View::make('accounts.show')->with('account', $account);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function store()
    {

        $account = $this->_repository->store(Input::all());

        if (!$account->id) {
            // did not save, return with error:
            Session::flash('error', 'Could not save the new account. Please check the form.');

            return View::make('accounts.create')->withErrors($account->errors());
        } else {
            // saved! return to wherever.
            Session::flash('success', 'Account "' . $account->name . '" created!');
            if (Input::get('create') == '1') {
                return Redirect::route('accounts.create')->withInput();
            } else {
                return Redirect::route('accounts.index');
            }
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update()
    {
        $account = $this->_repository->update(Input::all());
        Session::flash('success', 'Account "' . $account->name . '" updated.');

        return Redirect::route('accounts.index');
    }


}
