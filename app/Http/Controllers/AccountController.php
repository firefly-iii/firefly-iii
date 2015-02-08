<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Config;
use FireflyIII\Http\Requests;
use FireflyIII\Http\Requests\AccountFormRequest;
use Illuminate\Http\Request;
use View;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers
 */
class AccountController extends Controller
{
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

    public function index($what = 'default')
    {
        $subTitle     = Config::get('firefly.subTitlesByIdentifier.' . $what);
        $subTitleIcon = Config::get('firefly.subIconsByIdentifier.' . $what);
        $types        = Config::get('firefly.accountTypesByIdentifier.' . $what);
        $accounts     = Auth::user()->accounts()->accountTypeIn($types)->get(['accounts.*']);

        return view('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
    }

    public function store(AccountFormRequest $request)
    {

    }

}
