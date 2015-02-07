<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Navigation;
use Preferences;
use Redirect;
use Session;
use URL;

/**
 * Class HomeController
 *
 * @package FireflyIII\Http\Controllers
 */
class HomeController extends Controller
{


    /**
     *
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('range');
        //$this->middleware('guest');
    }

    /**
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $count         = Auth::user()->accounts()->accountTypeIn(['Asset account', 'Default account'])->count();
        $title         = 'Firefly';
        $subTitle      = 'What\'s playing?';
        $mainTitleIcon = 'fa-fire';
        $transactions  = [];
        $frontPage     = Preferences::get('frontPageAccounts', []);
        $start         = Session::get('start', Carbon::now()->startOfMonth());
        $end           = Session::get('end', Carbon::now()->endOfMonth());

        if ($frontPage->data == []) {
            $accounts = Auth::user()->accounts()->accountTypeIn(['Default account', 'Asset account'])->get(['accounts.*']);
        } else {
            $accounts = Auth::user()->accounts()->whereIn('id', $frontPage->data)->get(['accounts.*']);
        }

        foreach ($accounts as $account) {
            $set = Auth::user()
                ->transactionjournals()
                ->with(['transactions', 'transactioncurrency', 'transactiontype'])
                ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')->where('accounts.id', $account->id)
                ->where('date', '>=', $start->format('Y-m-d'))
                ->where('date', '<=', $end->format('Y-m-d'))
                ->orderBy('transaction_journals.date', 'DESC')
                ->orderBy('transaction_journals.id', 'DESC')
                ->take(10)
                ->get(['transaction_journals.*']);
            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }
//        var_dump($transactions);

        return view('index', compact('count', 'title', 'subTitle', 'mainTitleIcon', 'transactions'));
    }

    /**
     * @param string $range
     *
     * @return mixed
     */
    public function rangeJump($range)
    {

        $valid = ['1D', '1W', '1M', '3M', '6M', '1Y',];

        if (in_array($range, $valid)) {
            Preferences::set('viewRange', $range);
            Session::forget('range');
        }

        return Redirect::to(URL::previous());
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionNext()
    {
        $range = Session::get('range');
        $start = Session::get('start');

        Session::put('start', Navigation::jumpToNext($range, clone $start));

        return Redirect::to(URL::previous());

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionPrev()
    {
        $range = Session::get('range');
        $start = Session::get('start');

        Session::put('start', Navigation::jumpToPrevious($range, clone $start));

        return Redirect::to(URL::previous());
    }

}
