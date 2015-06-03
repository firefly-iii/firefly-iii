<?php namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Config;
use FireflyIII\Models\TransactionJournal;
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
        Session::clear();

        // encrypt transaction journal description
        $set = TransactionJournal::where('encrypted', 0)->take(100)->get();
        /** @var TransactionJournal $entry */
        foreach ($set as $entry) {
            $description        = $entry->description;
            $entry->description = $description;
            $entry->save();
        }
        unset($set, $entry, $description);

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

        $title             = 'Firefly';
        $subTitle          = trans('firefly.welcomeBack');
        $mainTitleIcon     = 'fa-fire';
        $transactions      = [];
        $frontPage         = Preferences::get('frontPageAccounts', []);
        $start             = Session::get('start', Carbon::now()->startOfMonth());
        $end               = Session::get('end', Carbon::now()->endOfMonth());
        $accounts          = $repository->getFrontpageAccounts($frontPage);
        $savings           = $repository->getSavingsAccounts();
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function routes()
    {
        $directory = '/vagrant_data/Sites/firefly-iii-help';
        $languages = array_keys(Config::get('firefly.lang'));
        $routes    = [];
        $ignored   = [
            'debugbar.openhandler', 'debugbar.assets.css', 'debugbar.assets.js', 'register', 'routes', 'daterange',
            'flush', 'delete-account-post', 'change-password-post', 'logout', 'login', 'tags.hideTagHelp',
            'budgets.postIncome', 'flush'
        ];

        $ignoreMatch = ['.store', '.update', '.destroy', 'json.'];

        $routeCollection = Route::getRoutes();
        /** @var \Illuminate\Routing\Route $object */
        foreach ($routeCollection as $object) {
            // get name:
            $name = $object->getName();
            // has name and not in ignore list?
            if (strlen($name) > 0 && !in_array($name, $ignored)) {

                // not in ignoreMatch?
                $continue = true;
                foreach ($ignoreMatch as $ignore) {
                    $match = strpos($name, $ignore);
                    if (!($match === false)) {
                        $continue = false;
                    }
                }
                unset($ignore, $match);

                if ($continue) {

                    $routes[] = $name;

                    // check all languages:
                    foreach ($languages as $lang) {
                        $file = $directory . '/' . $lang . '/' . $name . '.md';
                        if (!file_exists($file)) {
                            touch($file);
                            echo $name . '<br />';
                        }
                    }
                }


            }

        }

        // loop directories with language file.
        // tag the ones not in the list of approved routes.
        foreach ($languages as $lang) {
            $dir = $directory . '/' . $lang;
            $set = scandir($dir);
            foreach ($set as $entry) {
                if ($entry != '.' && $entry != '..') {
                    $name = str_replace('.md', '', $entry);
                    if (!in_array($name, $routes)) {
                        $file = $dir . '/' . $entry;
                        unlink($file);
                    }
                }
            }
        }
        echo 'Done!';
    }


}
