<?php namespace FireflyIII\Http\Controllers;

use Amount;
use Artisan;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
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

    public function dateRange()
    {

        $start         = new Carbon(Input::get('start'));
        $end           = new Carbon(Input::get('end'));
        $label         = Input::get('label');
        $isCustomRange = false;

        // check if the label is "everything" or "Custom range" which will betray
        // a possible problem with the budgets.
        if ($label === strval(trans('firefly.everything')) || $label === strval(trans('firefly.customRange'))) {
            $isCustomRange = true;
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
            foreach ($tag->transactionjournals()->get() as $journal) {
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
     * @param ARI $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index(ARI $repository)
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
        $frontPage     = Preferences::get('frontPageAccounts', []);
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end               = session('end', Carbon::now()->endOfMonth());
        $showTour          = Preferences::get('tour', true)->data;
        $accounts          = $repository->getAccountsById($frontPage->data);
        $savings           = $repository->getSavingsAccounts();
        $piggyBankAccounts = $repository->getPiggyBankAccounts();


        $savingsTotal = 0;
        foreach ($savings as $savingAccount) {
            $savingsTotal = bcadd($savingsTotal, Steam::balance($savingAccount, $end));
        }

        $sum = $repository->sumOfEverything();

        if (bccomp($sum, '0') !== 0) {
            Session::flash('error', strval(trans('firefly.unbalanced_error', ['amount' => Amount::format($sum, false)])));
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
            //            'logout', 'register', 'bills.rescan', 'attachments.download', 'attachments.preview',
            //            'budgets.income', 'csv.download-config', 'currency.default', 'export.status', 'export.download',
            //            'json.', 'help.', 'piggy-banks.addMoney', 'piggy-banks.removeMoney', 'rules.rule.up', 'rules.rule.down',
            //            'rules.rule-group.up', 'rules.rule-group.down', 'debugbar',
        ];
        $routes = Route::getRoutes();
        /** @var \Illuminate\Routing\Route $route */
        foreach ($routes as $route) {

            $name    = $route->getName();
            $methods = $route->getMethods();

            if (!is_null($name) && in_array('GET', $methods) && !$this->startsWithAny($ignore, $name)) {
                echo $name . '<br>';

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
    private function startsWithAny(array $array, string $needle): bool
    {
        foreach ($array as $entry) {
            if ((substr($needle, 0, strlen($entry)) === $entry)) {
                return true;
            }
        }

        return false;
    }
}
