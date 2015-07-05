<?php namespace FireflyIII\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Config;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Input;
use Preferences;
use Redirect;
use Route;
use Session;
use Steam;

/**
 * Class HomeController
 *
 * @package FireflyIII\Http\Controllers
 */
class HomeController extends Controller
{

    public function dateRange()
    {
        $start = new Carbon(Input::get('start'));
        $end   = new Carbon(Input::get('end'));

        $diff = $start->diffInDays($end);

        if ($diff > 50) {
            Session::flash('warning', $diff . ' days of data may take a while to load.');
        }

        Session::put('start', $start);
        Session::put('end', $end);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function flush()
    {

        Preferences::mark();

        // get all tags.
        // update all counts:
        $tags = Tag::get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            foreach ($tag->transactionjournals()->get() as $journal) {
                $count              = $journal->tags()->count();
                $journal->tag_count = $count;
                $journal->save();
            }
        }



        Session::clear();

        return Redirect::route('index');
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $types = Config::get('firefly.accountTypesByIdentifier.asset');
        $count = $repository->countAccounts($types);


        if ($count == 0) {
            return Redirect::route('new-user.index');
        }

        $title         = 'Firefly';
        $subTitle      = trans('firefly.welcomeBack');
        $mainTitleIcon = 'fa-fire';
        $transactions  = [];
        $frontPage     = Preferences::get('frontPageAccounts', []);
        $start         = Session::get('start', Carbon::now()->startOfMonth());
        $end           = Session::get('end', Carbon::now()->endOfMonth());

        $accounts = $repository->getFrontpageAccounts($frontPage);
        $savings  = $repository->getSavingsAccounts();

        $piggyBankAccounts = $repository->getPiggyBankAccounts();


        $savingsTotal = 0;
        foreach ($savings as $savingAccount) {
            $savingsTotal += Steam::balance($savingAccount, $end);
        }

        $sum = $repository->sumOfEverything();

        if ($sum != 0) {
            Session::flash(
                'error', 'Your transactions are unbalanced. This means a'
                         . ' withdrawal, deposit or transfer was not stored properly. '
                         . 'Please check your accounts and transactions for errors.'
            );
        }

        foreach ($accounts as $account) {
            $set = $repository->getFrontpageTransactions($account, $start, $end);

            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        return view('index', compact('count', 'title', 'savings', 'subTitle', 'mainTitleIcon', 'transactions', 'savingsTotal', 'piggyBankAccounts'));
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Http\RedirectResponse|string
     */
    public function routes()
    {
        if (!Auth::user()->hasRole('owner')) {
            Session::flash('warning', 'This page is broken.');

            return Redirect::route('index');
        }

        // get all routes:
        $routeCollection = Route::getRoutes();
        /** @var \Illuminate\Routing\Route $value */
        foreach ($routeCollection as $value) {
            $name    = $value->getName();
            $methods = $value->getMethods();
            $isPost  = in_array('POST', $methods);
            $index   = str_replace('.', '-', $name);

            if (strlen($name) > 0 && !$isPost) {
                echo "'" . $index . "' => '" . $name . "',<br />";
            }
        }

        return '&nbsp;';
    }
}
