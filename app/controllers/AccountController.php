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
                View::share('subTitleIcon', 'fa-money');
                View::share('subTitle', 'Asset accounts');
                break;
            case 'expense':
                View::share('subTitleIcon', 'fa-shopping-cart');
                View::share('subTitle', 'Expense accounts');
                break;
            case 'revenue':
                View::share('subTitleIcon', 'fa-download');
                View::share('subTitle', 'Revenue accounts');
                break;
        }
        return View::make('accounts.index')->with('what', $what);
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
     * @param $account
     *
     * @return \Illuminate\View\View
     */
    public function sankeyOut($account)
    {

        /*
         * Get the stuff.
         */
        $start = Session::get('start');
        $end   = Session::get('end');
        $query = \TransactionJournal::withRelevantData()
                                    ->defaultSorting()
                                    ->accountIs($account)
                                    ->after($start)
                                    ->before($end);
        $set   = $query->get(['transaction_journals.*']);
        /*
         * Arrays we need:
         */
        $collection = [];
        $filtered   = [];
        $result     = [];
        /** @var \TransactionJournal $entry */
        foreach ($set as $entry) {
            switch ($entry->transactionType->type) {
                case 'Withdrawal':
                    /** @var Budget $budget */
                    $budget = isset($entry->budgets[0]) ? $entry->budgets[0] : null;
                    $from   = $entry->transactions[0]->account->name;
                    $amount = floatval($entry->transactions[1]->amount);
                    if ($budget) {
                        $to = $budget->name;
                    } else {
                        $to = '(no budget)';
                    }
                    $collection[] = [$from, $to, $amount];

                    // also make one for the budget:
                    $from     = $to;
                    $category = $entry->categories()->first();
                    if ($category) {
                        $to = ' ' . $category->name;
                    } else {
                        $to = '(no category)';
                    }
                    $collection[] = [$from, $to, $amount];
                    break;
            }
        }

        /*
         * To break "cycles", aka money going back AND forth Firefly searches for previously existing
         * key sets (in reversed order) and if we find them, fix it.
         *
         * If the from-to amount found is larger than the amount going back, the amount going back
         * is removed and substracted from the current amount.
         *
         * If the from-to amount found is less than the amount going back, the entry is ignored
         * but substracted from the amount going back.
         */
        foreach ($collection as $current) {
            list($from, $to, $amount) = $current;
            $key      = $from . $to;
            $reversed = $to . $from;
            if (!isset($result[$reversed])) {
                if (isset($result[$key])) {
                    $filtered[$key]['amount'] += $amount;
                } else {
                    $filtered[$key] = ['from' => $from, 'to' => $to, 'amount' => $amount];
                }
            } else {
                /*
                 * If there is one, see which one will make it:
                 */
                $otherAmount = $result[$reversed]['amount'];
                if ($amount >= $otherAmount) {
                    unset($result[$reversed]);
                    $amount = $amount - $otherAmount;
                    // set:
                    if (isset($result[$key])) {
                        $filtered[$key]['amount'] += $amount;
                    } else {
                        $filtered[$key] = ['from' => $from, 'to' => $to, 'amount' => $amount];
                    }
                } else {
                    $filtered[$reversed]['amount'] -= $amount;
                }
            }

        }
        /*
         * Take out the keys:
         */
        foreach ($filtered as $key => $entry) {
            $result[] = [$entry['from'],$entry['to'],$entry['amount']];
        }


        /*
         * Loop it again to add the amounts.
         */
        return Response::json($result);
    }


    /**
     * @return \Illuminate\View\View
     */
    public function create($what)
    {
        switch ($what) {
            case 'asset':
                View::share('subTitleIcon', 'fa-money');
                break;
            case 'expense':
                View::share('subTitleIcon', 'fa-shopping-cart');
                break;
            case 'revenue':
                View::share('subTitleIcon', 'fa-download');
                break;
        }

        return View::make('accounts.create')->with('subTitle', 'Create a new ' . $what . ' account')->with('what', $what);
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
                View::share('subTitleIcon', 'fa-money');
                break;
            case 'Expense account':
            case 'Beneficiary account':
                View::share('subTitleIcon', 'fa-shopping-cart');
                break;
            case 'Revenue account':
                View::share('subTitleIcon', 'fa-download');
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


        return View::make('accounts.edit')->with('account', $account)->with('openingBalance', $openingBalance)->with(
            'subTitle', 'Edit ' . strtolower(
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
                View::share('subTitleIcon', 'fa-money');
                break;
            case 'Expense account':
            case 'Beneficiary account':
                View::share('subTitleIcon', 'fa-shopping-cart');
                break;
            case 'Revenue account':
                View::share('subTitleIcon', 'fa-download');
                break;
        }


        //$data = $this->_accounts->show($account, 40);
        return View::make('accounts.show')
                   ->with('account', $account)
                   ->with('subTitle', 'Details for ' . strtolower($account->accountType->type) . ' "' . $account->name . '"');
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
            case 'create_another':
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
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function update(Account $account)
    {
        /** @var \Account $account */
        $account = $this->_repository->update($account, Input::all());
        if ($account->validate()) {
            Session::flash('success', 'Account "' . $account->name . '" updated.');
            switch ($account->accountType->type) {
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

        } else {
            Session::flash('error', 'Could not update account: ' . $account->errors()->first());

            return Redirect::route('accounts.edit', $account->id)->withInput()->withErrors($account->errors());
        }
    }


}
