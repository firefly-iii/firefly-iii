<?php
/**
 * HomeController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Preferences;
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
    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     */
    public function dateRange(Request $request)
    {

        $start         = new Carbon($request->get('start'));
        $end           = new Carbon($request->get('end'));
        $label         = $request->get('label');
        $isCustomRange = false;

        Log::debug('Received dateRange', ['start' => $request->get('start'), 'end' => $request->get('end'), 'label' => $request->get('label')]);

        // check if the label is "everything" or "Custom range" which will betray
        // a possible problem with the budgets.
        if ($label === strval(trans('firefly.everything')) || $label === strval(trans('firefly.customRange'))) {
            $isCustomRange = true;
            Log::debug('Range is now marked as "custom".');
        }

        $diff = $start->diffInDays($end);

        if ($diff > 50) {
            Session::flash('warning', strval(trans('firefly.warning_much_data', ['days' => $diff])));
        }

        Session::put('is_custom_range', $isCustomRange);
        Session::put('start', $start);
        Session::put('end', $end);
    }

    /**
     * @throws FireflyException
     */
    public function displayError()
    {
        throw new FireflyException('A very simple test error.');
    }

    /**
     * @param TagRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function flush(TagRepositoryInterface $repository)
    {

        Preferences::mark();

        // get all tags.
        // update all counts:
        $tags = $repository->get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            foreach ($tag->transactionJournals()->get() as $journal) {
                $count              = $journal->tags()->count();
                $journal->tag_count = $count;
                $journal->save();
            }
        }


        Session::clear();
        Artisan::call('cache:clear');

        return redirect(route('index'));
    }

    /**
     * @param ARI                  $repository
     * @param AccountCrudInterface $crud
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function index(ARI $repository, AccountCrudInterface $crud)
    {

        $types = config('firefly.accountTypesByIdentifier.asset');
        $count = $repository->countAccounts($types);

        if ($count == 0) {
            return redirect(route('new-user.index'));
        }

        $title         = 'Firefly';
        $subTitle      = trans('firefly.welcomeBack');
        $mainTitleIcon = 'fa-fire';
        $transactions  = [];
        $frontPage     = Preferences::get(
            'frontPageAccounts', $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray()
        );
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end               = session('end', Carbon::now()->endOfMonth());
        $showTour          = Preferences::get('tour', true)->data;
        $accounts          = $crud->getAccountsById($frontPage->data);
        $savings           = $repository->getSavingsAccounts($start, $end);
        $piggyBankAccounts = $repository->getPiggyBankAccounts($start, $end);


        $savingsTotal = '0';
        foreach ($savings as $savingAccount) {
            $savingsTotal = bcadd($savingsTotal, Steam::balance($savingAccount, $end));
        }

        foreach ($accounts as $account) {
            $set = $repository->journalsInPeriod(new Collection([$account]), [], $start, $end);
            $set = $set->splice(0, 10);

            if (count($set) > 0) {
                $transactions[] = [$set, $account];
            }
        }

        return view(
            'index', compact('count', 'showTour', 'title', 'savings', 'subTitle', 'mainTitleIcon', 'transactions', 'savingsTotal', 'piggyBankAccounts')
        );
    }

    /**
     * Display a list of named routes. Excludes some that cannot be "shown". This method
     * is used to generate help files (down the road).
     */
    public function routes()
    {
        // these routes are not relevant for the help pages:
        $ignore = [
        ];
        $routes = Route::getRoutes();
        /** @var \Illuminate\Routing\Route $route */
        foreach ($routes as $route) {

            $name    = $route->getName();
            $methods = $route->getMethods();
            $search  = [
                '{account}', '{what}', '{rule}', '{tj}', '{category}', '{budget}', '{code}', '{date}', '{attachment}', '{bill}', '{limitrepetition}',
                '{currency}', '{jobKey}', '{piggyBank}', '{ruleGroup}', '{rule}', '{route}', '{unfinishedJournal}',
                '{reportType}', '{start_date}', '{end_date}', '{accountList}', '{tag}', '{journalList}',

            ];
            $replace = [1, 'asset', 1, 1, 1, 1, 'abc', '2016-01-01', 1, 1, 1, 1, 1, 1, 1, 1, 'index', 1,
                        'default', '20160101', '20160131', '1,2', 1, '1,2',
            ];
            if (count($search) != count($replace)) {
                echo 'count';
                exit;
            }
            $url = str_replace($search, $replace, $route->getUri());

            if (!is_null($name) && in_array('GET', $methods) && !$this->startsWithAny($ignore, $name)) {
                echo '<a href="/' . $url . '" title="' . $name . '">' . $name . '</a><br>' . "\n";

            }
        }

        return '<hr>';
    }


    /**
     * @param array  $array
     * @param string $needle
     *
     * @return bool
     */
    private
    function startsWithAny(
        array $array, string $needle
    ): bool
    {
        foreach ($array as $entry) {
            if ((substr($needle, 0, strlen($entry)) === $entry)) {
                return true;
            }
        }

        return false;
    }

}
