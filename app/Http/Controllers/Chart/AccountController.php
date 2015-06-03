<?php

namespace FireflyIII\Http\Controllers\Chart;

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
    /**
     * Shows the balances for all the user's accounts.
     *
     * @param GChart                     $chart
     * @param AccountRepositoryInterface $repository
     *
     * @param                            $year
     * @param                            $month
     * @param bool                       $shared
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function all(GChart $chart, AccountRepositoryInterface $repository, $year, $month, $shared = false)
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
            return Response::json($cache->get());
        }


        $chart->addColumn(trans('firefly.dayOfMonth'), 'date');

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


        $index = 1;
        /** @var Account $account */
        foreach ($accounts as $account) {
            $chart->addColumn(trans('firefly.balanceFor', ['name' => $account->name]), 'number');
            $chart->addCertainty($index);
            $index++;
        }
        $current = clone $start;
        $current->subDay();
        $today = Carbon::now();
        while ($end >= $current) {
            $row     = [clone $current];
            $certain = $current < $today;
            foreach ($accounts as $account) {
                $row[] = Steam::balance($account, $current);
                $row[] = $certain;
            }
            $chart->addRowArray($row);
            $current->addDay();
        }
        $chart->generate();

        $data = $chart->getData();
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param GChart                     $chart
     * @param AccountRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(GChart $chart, AccountRepositoryInterface $repository)
    {
        $chart->addColumn(trans('firefly.dayOfMonth'), 'date');

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
            return Response::json($cache->get());
        }


        $index = 1;
        /** @var Account $account */
        foreach ($accounts as $account) {
            $chart->addColumn(trans('firefly.balanceFor', ['name' => $account->name]), 'number');
            $chart->addCertainty($index);
            $index++;
        }
        $current = clone $start;
        $current->subDay();
        $today = Carbon::now();
        while ($end >= $current) {
            $row     = [clone $current];
            $certain = $current < $today;
            foreach ($accounts as $account) {
                $row[] = Steam::balance($account, $current);
                $row[] = $certain;
            }
            $chart->addRowArray($row);
            $current->addDay();
        }
        $chart->generate();

        $data = $chart->getData();
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * Shows an account's balance for a single month.
     *
     * @param GChart  $chart
     * @param Account $account
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function single(GChart $chart, Account $account)
    {
        $chart->addColumn(trans('firefly.dayOfMonth'), 'date');
        $chart->addColumn(trans('firefly.balanceFor', ['name' => $account->name]), 'number');
        $chart->addCertainty(1);

        $start   = Session::get('start', Carbon::now()->startOfMonth());
        $end     = Session::get('end', Carbon::now()->endOfMonth());
        $current = clone $start;
        $today   = new Carbon;

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('frontpage');
        $cache->addProperty('single');
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        while ($end >= $current) {
            $certain = $current < $today;
            $chart->addRow(clone $current, Steam::balance($account, $current), $certain);
            $current->addDay();
        }


        $chart->generate();

        $data = $chart->getData();
        $cache->store($data);

        return Response::json($data);
    }
}
