<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Redirect;
use Session;
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
        $subTitleIcon = Config::get('firefly.subTitlesByIdentifier.' . $what);
        $subTitle     = 'Create a new ' . e($what) . ' account';

        //\FireflyIII\Forms\Tags::ffAmount('12');

        return view('accounts.create', compact('subTitleIcon', 'what', 'subTitle'));

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
        $accounts     = Auth::user()->accounts()->accountTypeIn($types)->get(['accounts.*']);

        return view('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
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

        return Redirect::route('accounts.index', $request->input('what'));

    }

}
