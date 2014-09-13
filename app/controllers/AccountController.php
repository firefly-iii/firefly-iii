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
     * @param AI  $accounts
     */
    public function __construct(ARI $repository, AI $accounts)
    {
        $this->_accounts   = $accounts;
        $this->_repository = $repository;
        View::share('mainTitleIcon', 'fa-credit-card');
        View::share('title', 'Accounts');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function create($what)
    {
        View::share('subTitleIcon', 'fa-money');

        return View::make('accounts.create')->with('subTitle', 'Create a new ' . $what . ' account')->with(
            'what', $what
        );
    }

    /**
     * @return $this
     */
    public function asset()
    {
        View::share('subTitleIcon','fa-money');

        $accounts = $this->_repository->getOfTypes(['Asset account', 'Default account']);

        return View::make('accounts.asset')->with('subTitle', 'Asset accounts')->with(
            'accounts', $accounts
        );
    }

    /**
     * @return $this
     */
    public function expense()
    {
        View::share('subTitleIcon','fa-shopping-cart');

        $accounts = $this->_repository->getOfTypes(['Expense account', 'Beneficiary account']);

        return View::make('accounts.expense')->with('subTitle', 'Expense accounts')->with(
            'accounts', $accounts
        );
    }

    /**
     * @return $this
     */
    public function revenue()
    {
        View::share('subTitleIcon','fa-download');

        $accounts = $this->_repository->getOfTypes(['Revenue account']);

        return View::make('accounts.revenue')->with('subTitle', 'Revenue accounts')->with(
            'accounts', $accounts
        );
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
        $this->_repository->destroy($account);
        Session::flash('success', 'The account was deleted.');
        switch ($type) {
            case 'Asset account':
            case 'Default account':
                return Redirect::route('accounts.asset');
                break;
            case 'Expense account':
            case 'Beneficiary account':
                return Redirect::route('accounts.expense');
                break;
            case 'Revenue account':
                return Redirect::route('accounts.revenue');
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
        $openingBalance = $this->_accounts->openingBalanceTransaction($account);
        return View::make('accounts.edit')->with('account', $account)->with('openingBalance', $openingBalance)

            ->with('subTitle', 'Edit ' . strtolower($account->accountType->type) . ' "' . $account->name . '"');
    }

    /**
     * @return $this
     */
    public function index()
    {
        return View::make('error')->with('message', 'This view has been disabled');
//        $accounts = $this->_repository->get();
//        $set      = [
//            'personal'      => [],
//            'beneficiaries' => []
//        ];
//        foreach ($accounts as $account) {
//            switch ($account->accounttype->type) {
//                case 'Default account':
//                    $set['personal'][] = $account;
//                    break;
//                case 'Beneficiary account':
//                    $set['beneficiaries'][] = $account;
//                    break;
//            }
//        }
//
//        return View::make('accounts.index')->with('accounts', $set)->with('title', 'All your accounts');
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function show(Account $account)
    {
        $data = $this->_accounts->show($account, 40);

        return View::make('accounts.show')->with('account', $account)->with('show', $data)->with(
            'subTitle',
            'Details for ' . strtolower($account->accountType->type) . ' "' . $account->name . '"'
        );
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store()
    {

        $data         = Input::all();
        $data['what'] = isset($data['what']) && $data['what'] != '' ? $data['what'] : 'asset';


        switch ($data['what']) {
            default:
            case 'asset':
                $data['account_type'] = 'Asset account';
                break;
            case 'expense':
                $data['account_type'] = 'Expense account';
                break;
            case 'revenue':
                $data['account_type'] = 'Revenue account';
                break;

        }
        $account = $this->_repository->store($data);

        if ($account->validate()) {
            // saved! return to wherever.
            Session::flash('success', 'Account "' . $account->name . '" created!');
            if (intval(Input::get('create')) === 1) {
                return Redirect::route('accounts.create', $data['what'])->withInput();
            } else {
                return Redirect::route('accounts.index');
            }
        } else {
            // did not save, return with error:
            Session::flash('error', 'Could not save the new account: ' . $account->errors()->first());

            return Redirect::route('accounts.create', $data['what'])->withErrors($account->errors())->withInput();

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
