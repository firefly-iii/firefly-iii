<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Redirect;
use Session;
use Steam;
use View;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers
 */
class AccountController extends Controller
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
     * @return \Illuminate\View\View
     */
    public function create($what = 'asset')
    {
        $subTitleIcon = Config::get('firefly.subIconsByIdentifier.' . $what);
        $subTitle     = 'Create a new ' . e($what) . ' account';

        return view('accounts.create', compact('subTitleIcon', 'what', 'subTitle'));

    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\View\View
     */
    public function delete(Account $account)
    {
        $subTitle = 'Delete ' . strtolower(e($account->accountType->type)) . ' "' . e($account->name) . '"';

        return view('accounts.delete', compact('account', 'subTitle'));
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Account $account, AccountRepositoryInterface $repository)
    {

        $type     = $account->accountType->type;
        $typeName = Config::get('firefly.shortNamesByFullName.' . $type);
        $name     = $account->name;

        $repository->destroy($account);

        Session::flash('success', 'The ' . e($typeName) . ' account "' . e($name) . '" was deleted.');

        return Redirect::route('accounts.index', $typeName);
    }

    /**
     * @param Account                    $account
     * @param AccountRepositoryInterface $repository
     *
     * @return View
     */
    public function edit(Account $account, AccountRepositoryInterface $repository)
    {
        $what           = Config::get('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle       = 'Edit ' . strtolower(e($account->accountType->type)) . ' "' . e($account->name) . '"';
        $subTitleIcon   = Config::get('firefly.subIconsByIdentifier.' . $what);
        $openingBalance = $repository->openingBalanceTransaction($account);

        // pre fill some useful values.

        // the opening balance is tricky:
        $openingBalanceAmount = null;
        if ($openingBalance) {
            $transaction          = $openingBalance->transactions()->where('account_id', $account->id)->first();
            $openingBalanceAmount = $transaction->amount;
        }

        $preFilled = [
            'accountRole'        => $account->getMeta('accountRole'),
            'openingBalanceDate' => $openingBalance ? $openingBalance->date->format('Y-m-d') : null,
            'openingBalance'     => $openingBalanceAmount
        ];
        Session::flash('preFilled', $preFilled);

        return view('accounts.edit', compact('account', 'subTitle', 'subTitleIcon', 'openingBalance', 'what'));
    }

    /**
     * @param string $what
     *
     * @return View
     */
    public function index($what = 'default')
    {
        $subTitle     = Config::get('firefly.subTitlesByIdentifier.' . $what);
        $subTitleIcon = Config::get('firefly.subIconsByIdentifier.' . $what);
        $types        = Config::get('firefly.accountTypesByIdentifier.' . $what);
        $size         = 50;
        $page         = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $offset       = ($page - 1) * $size;


        // move to repository:
        $set   = Auth::user()->accounts()->with(
            ['accountmeta' => function (HasMany $query) {
                $query->where('name', 'accountRole');
            }]
        )->accountTypeIn($types)->take($size)->offset($offset)->orderBy('accounts.name', 'ASC')->get(['accounts.*']);
        $total = Auth::user()->accounts()->accountTypeIn($types)->count();

        // last activity:
        $start = clone Session::get('start');
        $start->subDay();
        $set->each(
            function (Account $account) use ($start) {
                $lastTransaction = $account->transactions()->leftJoin(
                    'transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
                )->orderBy('transaction_journals.date', 'DESC')->first(['transactions.*', 'transaction_journals.date']);
                if ($lastTransaction) {
                    $account->lastActivityDate = $lastTransaction->transactionjournal->date;
                } else {
                    $account->lastActivityDate = null;
                }
                $account->startBalance = Steam::balance($account, $start);
                $account->endBalance   = Steam::balance($account, clone Session::get('end'));
            }
        );

        $accounts = new LengthAwarePaginator($set, $total, $size, $page);
        $accounts->setPath(route('accounts.index', $what));


        return view('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
    }

    /**
     * @param Account                    $account
     * @param AccountRepositoryInterface $repository
     *
     * @return View
     */
    public function show(Account $account, AccountRepositoryInterface $repository)
    {
        $page         = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $subTitleIcon = Config::get('firefly.subTitlesByIdentifier.' . $account->accountType->type);
        $what         = Config::get('firefly.shortNamesByFullName.' . $account->accountType->type);
        $journals     = $repository->getJournals($account, $page);
        $subTitle     = 'Details for ' . strtolower(e($account->accountType->type)) . ' "' . e($account->name) . '"';
        $journals->setPath('accounts/show/' . $account->id);

        return view('accounts.show', compact('account', 'what', 'subTitleIcon', 'journals', 'subTitle'));
    }

    /**
     * @param AccountFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AccountFormRequest $request, AccountRepositoryInterface $repository)
    {
        $accountData = [
            'name'                   => $request->input('name'),
            'accountType'            => $request->input('what'),
            'active'                 => true,
            'user'                   => Auth::user()->id,
            'accountRole'            => $request->input('accountRole'),
            'openingBalance'         => floatval($request->input('openingBalance')),
            'openingBalanceDate'     => new Carbon($request->input('openingBalanceDate')),
            'openingBalanceCurrency' => intval($request->input('balance_currency_id')),

        ];
        $account     = $repository->store($accountData);

        Session::flash('success', 'New account "' . $account->name . '" stored!');

        if (intval(Input::get('create_another')) === 1) {
            return Redirect::route('accounts.create', $request->input('what'))->withInput();
        }

        return Redirect::route('accounts.index', $request->input('what'));

    }

    /**
     * @param Account                    $account
     * @param AccountFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Account $account, AccountFormRequest $request, AccountRepositoryInterface $repository)
    {
        $what        = Config::get('firefly.shortNamesByFullName.' . $account->accountType->type);
        $accountData = [
            'name'                   => $request->input('name'),
            'active'                 => $request->input('active'),
            'user'                   => Auth::user()->id,
            'accountRole'            => $request->input('accountRole'),
            'openingBalance'         => floatval($request->input('openingBalance')),
            'openingBalanceDate'     => new Carbon($request->input('openingBalanceDate')),
            'openingBalanceCurrency' => intval($request->input('balance_currency_id')),
        ];

        $repository->update($account, $accountData);

        Session::flash('success', 'Account "' . $account->name . '" updated.');

        if (intval(Input::get('return_to_edit')) === 1) {
            return Redirect::route('accounts.edit', $account->id);
        }

        return Redirect::route('accounts.index', $what);

    }

}
