<?php namespace FireflyIII\Http\Controllers;

use Preferences;
use Navigation;
use Redirect;
use URL;
use Session;

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
        // count is fake
        $count         = \Auth::user()->accounts()->accountTypeIn(['Asset account', 'Default account'])->count();
        $title         = 'Firefly';
        $subTitle      = 'What\'s playing?';
        $mainTitleIcon = 'fa-fire';
        $transactions = [];

        return view('index', compact('count', 'title', 'subTitle', 'mainTitleIcon','transactions'));
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
        Navigation::next();
        return Redirect::to(URL::previous());

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sessionPrev()
    {
        Navigation::prev();
        return Redirect::to(URL::previous());
    }

}
