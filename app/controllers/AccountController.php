<?php

use FireflyIII\Database\Account\Account as AccountRepository;
use FireflyIII\Exception\FireflyException;

/**
 * Class AccountController
 */
class AccountController extends BaseController
{

    /** @var array */
    protected $_accountTypesByIdentifier
        = [
            'asset'   => ['Default account', 'Asset account'],
            'expense' => ['Expense account', 'Beneficiary account'],
            'revenue' => ['Revenue account'],
        ];

    /** @var AccountRepository */
    protected $_repository;

    /** @var array */
    protected $_shortNamesByFullName
        = [
            'Default account'     => 'asset',
            'Asset account'       => 'asset',
            'Expense account'     => 'expense',
            'Beneficiary account' => 'expense',
            'Revenue account'     => 'revenue',
            'Cash account'        => 'cash',
        ];

    /** @var array */
    protected $_subIconsByIdentifier
        = [
            'asset'               => 'fa-money',
            'Asset account'       => 'fa-money',
            'Default account'     => 'fa-money',
            'Cash account'        => 'fa-money',
            'expense'             => 'fa-shopping-cart',
            'Expense account'     => 'fa-shopping-cart',
            'Beneficiary account' => 'fa-shopping-cart',
            'revenue'             => 'fa-download',
            'Revenue account'     => 'fa-download',
        ];
    /** @var array */
    protected $_subTitlesByIdentifier
        = [
            'asset'   => 'Asset accounts',
            'expense' => 'Expense accounts',
            'revenue' => 'Revenue accounts',
        ];

    /**
     * @param AccountRepository $repository
     */
    public function __construct(AccountRepository $repository)
    {
        $this->_repository = $repository;
        View::share('mainTitleIcon', 'fa-credit-card');
        View::share('title', 'Accounts');
    }

    /**
     * @param $what
     *
     * @return \Illuminate\View\View
     */
    public function create($what)
    {
        $subTitleIcon = $this->_subIconsByIdentifier[$what];
        $subTitle     = 'Create a new ' . e($what) . ' account';

        return View::make('accounts.create', compact('subTitleIcon', 'what', 'subTitle'));
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function delete(Account $account)
    {
        $subTitle = 'Delete ' . strtolower(e($account->accountType->type)) . ' "' . e($account->name) . '"';

        return View::make('accounts.delete', compact('account', 'subTitle'));
    }

    /**
     * @param Account $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Account $account)
    {

        $type     = $account->accountType->type;
        $typeName = $this->_shortNamesByFullName[$type];
        $name     = $account->name;

        $this->_repository->destroy($account);

        Session::flash('success', 'The ' . e($typeName) . ' account "' . e($name) . '" was deleted.');

        return Redirect::route('accounts.index', $typeName);
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function edit(Account $account)
    {

        $openingBalance = $this->_repository->openingBalanceTransaction($account);
        $subTitleIcon   = $this->_subIconsByIdentifier[$account->accountType->type];
        $subTitle       = 'Edit ' . strtolower(e($account->accountType->type)) . ' "' . e($account->name) . '"';

        // pre fill some useful values.
        $preFilled = [
            'account_role'       => $account->getMeta('accountRole'),
            'openingBalanceDate' => $openingBalance ? $openingBalance->date->format('Y-m-d') : null,
            'openingBalance'     => $openingBalance ? $openingBalance->getAmount($account) : null
        ];
        Session::flash('preFilled', $preFilled);

        return View::make('accounts.edit', compact('account', 'subTitle', 'openingBalance', 'subTitleIcon'));
    }

    /**
     *
     * @param string $what
     *
     * @return View
     * @throws FireflyException
     */
    public function index($what = 'default')
    {
        $subTitle     = $this->_subTitlesByIdentifier[$what];
        $subTitleIcon = $this->_subIconsByIdentifier[$what];

        $accounts = $this->_repository->getAccountsByType($this->_accountTypesByIdentifier[$what]);

        return View::make('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
    }

    /**
     * @param Account $account
     * @param string  $range
     *
     * @return $this
     */
    public function show(Account $account, $range = 'session')
    {
        $subTitleIcon = $this->_subIconsByIdentifier[$account->accountType->type];
        $what         = $this->_shortNamesByFullName[$account->accountType->type];
        $journals     = $this->_repository->getTransactionJournals($account, 50, $range);
        $subTitle     = 'Details for ' . strtolower(e($account->accountType->type)) . ' "' . e($account->name) . '"';

        return View::make('accounts.show', compact('account', 'what', 'range', 'subTitleIcon', 'journals', 'subTitle'));
    }

    /**
     * @return $this|\Illuminate\Http\RedirectResponse
     * @throws FireflyException
     */
    public function store()
    {

        $data = Input::except('_token');

        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not store account: ' . $messages['errors']->first());
        }

        // return to create screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('accounts.create', e($data['what']))->withInput();
        }

        // store:
        $this->_repository->store($data);
        Session::flash('success', 'Account "' . e($data['name']) . '" stored.');
        if ($data['post_submit_action'] == 'store') {
            return Redirect::route('accounts.index', e($data['what']));
        }

        return Redirect::route('accounts.create', e($data['what']))->withInput();
    }

    /**
     * @param Account $account
     *
     * @return $this
     * @throws FireflyException
     */
    public function update(Account $account)
    {
        $data         = Input::except('_token');
        $data['what'] = $this->_shortNamesByFullName[$account->accountType->type];


        // always validate:
        $messages = $this->_repository->validate($data);

        // flash messages:
        Session::flash('warnings', $messages['warnings']);
        Session::flash('successes', $messages['successes']);
        Session::flash('errors', $messages['errors']);
        if ($messages['errors']->count() > 0) {
            Session::flash('error', 'Could not update account: ' . $messages['errors']->first());
        }

        // return to update screen:
        if ($data['post_submit_action'] == 'validate_only' || $messages['errors']->count() > 0) {
            return Redirect::route('accounts.edit', $account->id)->withInput();
        }

        // update
        $this->_repository->update($account, $data);
        Session::flash('success', 'Account "' . e($data['name']) . '" updated.');

        // go back to list
        if ($data['post_submit_action'] == 'update') {
            return Redirect::route('accounts.index', e($data['what']));
        }

        // go back to update screen.
        return Redirect::route('accounts.edit', $account->id)->withInput(['post_submit_action' => 'return_to_edit']);
    }
}