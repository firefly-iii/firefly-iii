<?php

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;

/**
 * Class AccountController
 */
class AccountController extends \BaseController
{

    protected $_accounts;

    /**
     * @param ARI $accounts
     */
    public function __construct(ARI $accounts)
    {
        $this->_accounts = $accounts;

        View::share('menu', 'accounts');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $all = $this->_accounts->get();


        $list = [
            'personal'      => [],
            'beneficiaries' => [],
            'initial'       => [],
            'cash'          => []
        ];
        $total = count($all);

        foreach ($all as $account) {

            switch ($account->accounttype->description) {
                case 'Default account':
                    $list['personal'][] = $account;
                    break;
                case 'Cash account':
                    $list['cash'][] = $account;
                    break;
                case 'Initial balance account':
                    $list['initial'][] = $account;
                    break;
                case 'Beneficiary account':
                    $list['beneficiaries'][] = $account;
                    break;

            }
        }

        return View::make('accounts.index')->with('accounts', $list)->with('total', $total);
    }
//
//
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return View::make('accounts.create');
    }

    public function store()
    {

        $account = $this->_accounts->store(Input::all());

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

    public function edit($accountId)
    {
        $account = $this->_accounts->find($accountId);

        if ($account) {
            // find the initial balance transaction, if any:
            $openingBalance = $this->_accounts->findOpeningBalanceTransaction($account);
            return View::make('accounts.edit')->with('account', $account)->with('openingBalance',$openingBalance);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $accountId
     *
     * @return Response
     */
    public function show($accountId)
    {
        $account = $this->_accounts->find($accountId);
        return View::make('accounts.show')->with('account',$account);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update()
    {
        $account = $this->_accounts->update(Input::all());
        Session::flash('success','Account "'.$account->name.'" updated.');
        return Redirect::route('accounts.index');
    }

    public function delete($accountId) {
        $account = $this->_accounts->find($accountId);
        if($account) {
            return View::make('accounts.delete')->with('account',$account);
        }
    }

    /**
     * @param $accountId
     */
    public function destroy()
	{
        $result = $this->_accounts->destroy(Input::get('id'));
        if($result === true) {
            Session::flash('success','The account was deleted.');
            return Redirect::route('accounts.index');
        } else {
            Session::flash('danger','Could not delete the account. Check the logs to be sure.');
            return Redirect::route('accounts.index');
        }

	}


}
