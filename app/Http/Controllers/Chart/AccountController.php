<?php

namespace FireflyIII\Http\Controllers\Chart;

use App;
use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Grumpydictator\Gchart\GChart;
use Illuminate\Support\Collection;
use Preferences;
use Response;
use Session;
use Steam;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class AccountController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\Account\AccountChartGenerator */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = App::make('FireflyIII\Generator\Chart\Account\AccountChartGenerator');
    }


    /**
     * Shows the balances for all the user's accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @param                            $year
     * @param                            $month
     * @param bool                       $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(AccountRepositoryInterface $repository, $year, $month, $shared = false)
    {
        $start = new Carbon($year . '-' . $month . '-01');
        $end   = clone $start;
        $end->endOfMonth();

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('all');
        $cache->addProperty('accounts');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        /** @var Collection $accounts */
        $accounts = $repository->getAccounts(['Default account', 'Asset account']);
        if ($shared === false) {
            /** @var Account $account */
            foreach ($accounts as $index => $account) {
                if ($account->getMeta('accountRole') == 'sharedAsset') {
                    $accounts->forget($index);
                }
            }
        }

        // make chart:
        $data = $this->generator->all($accounts, $start, $end);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(AccountRepositoryInterface $repository)
    {
        $frontPage = Preferences::get('frontPageAccounts', []);
        $start     = Session::get('start', Carbon::now()->startOfMonth());
        $end       = Session::get('end', Carbon::now()->endOfMonth());
        $accounts  = $repository->getFrontpageAccounts($frontPage);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('frontpage');
        $cache->addProperty('accounts');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $data = $this->generator->frontpage($accounts, $start, $end);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * Shows an account's balance for a single month.
     *
     * @param Account $account
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function single(Account $account)
    {


        $start   = Session::get('start', Carbon::now()->startOfMonth());
        $end     = Session::get('end', Carbon::now()->endOfMonth());

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('frontpage');
        $cache->addProperty('single');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $data = $this->generator->single($account, $start, $end);
        $cache->store($data);

        return Response::json($data);
    }
}
