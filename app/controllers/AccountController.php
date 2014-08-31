<?php

use Firefly\Helper\Controllers\AccountInterface as AI;
use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

/**
 * Class AccountController
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class AccountController extends \BaseController
{

    protected $_repository;
    protected $_accounts;

    /**
     * @param ARI $repository
     * @param AI $accounts
     */
    public function __construct(ARI $repository, AI $accounts)
    {
        $this->_accounts   = $accounts;
        $this->_repository = $repository;
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return View::make('accounts.create')->with('title', 'Create account');
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function delete(Account $account)
    {
        return View::make('accounts.delete')->with('account', $account)
                   ->with('title', 'Delete account "' . $account->name . '"');
    }

    /**
     * @param Account $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Account $account)
    {

        $this->_repository->destroy($account);
        Session::flash('success', 'The account was deleted.');

        return Redirect::route('accounts.index');

    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function edit(Account $account)
    {
        $openingBalance = $this->_accounts->openingBalanceTransaction($account);
        return View::make('accounts.edit')->with('account', $account)->with('openingBalance', $openingBalance)->with('title','Edit account "'.$account->name.'"');
    }

    /**
     * @return $this
     */
    public function index()
    {
        $accounts = $this->_repository->get();
        $set      = [
            'personal'      => [],
            'beneficiaries' => []
        ];
        foreach ($accounts as $account) {
            switch ($account->accounttype->type) {
                case 'Default account':
                    $set['personal'][] = $account;
                    break;
                case 'Beneficiary account':
                    $set['beneficiaries'][] = $account;
                    break;
            }
        }

        return View::make('accounts.index')->with('accounts', $set)->with('title','All your accounts');
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function show(Account $account)
    {
        $data = $this->_accounts->show($account, 40);

        return View::make('accounts.show')->with('account', $account)->with('show', $data)->with('title',
            'Details for account "' . $account->name . '"');
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {

        $account = $this->_repository->store(Input::all());

        if ($account->validate()) {
            // saved! return to wherever.
            Session::flash('success', 'Account "' . $account->name . '" created!');
            if (intval(Input::get('create')) === 1) {
                return Redirect::route('accounts.create')->withInput();
            } else {
                return Redirect::route('accounts.index');
            }
        } else {
            // did not save, return with error:
            Session::flash('error', 'Could not save the new account: ' . $account->errors()->first());

            return Redirect::route('accounts.create')->withErrors($account->errors())->withInput();

        }
    }

    /**
     * @param Account $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Account $account)
    {
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
