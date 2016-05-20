<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Crud\Account\AccountCrudInterface;
use FireflyIII\Generator\Chart\Account\AccountChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Preferences;
use Response;
use Steam;

/** checked
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class AccountController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\Account\AccountChartGeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(AccountChartGeneratorInterface::class);
    }

    /**
     * Shows the balances for all the user's expense accounts.
     *
     * @param AccountCrudInterface $crud
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function expenseAccounts(AccountCrudInterface $crud)
    {
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = clone session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expenseAccounts');
        $cache->addProperty('accounts');
        if ($cache->has()) {
            return Response::json($cache->get());
        }
        $accounts = $crud->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);

        $start->subDay();
        $ids           = $accounts->pluck('id')->toArray();
        $startBalances = Steam::balancesById($ids, $start);
        $endBalances   = Steam::balancesById($ids, $end);

        $accounts->each(
            function (Account $account) use ($startBalances, $endBalances) {
                $id                  = $account->id;
                $startBalance        = $startBalances[$id] ?? '0';
                $endBalance          = $endBalances[$id] ?? '0';
                $diff                = bcsub($endBalance, $startBalance);
                $account->difference = round($diff, 2);
            }
        );


        $accounts = $accounts->sortByDesc(
            function (Account $account) {
                return $account->difference;
            }
        );

        $data = $this->generator->expenseAccounts($accounts, $start, $end);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param AccountCrudInterface $crud
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function frontpage(AccountCrudInterface $crud)
    {
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = clone session('end', Carbon::now()->endOfMonth());


        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('frontpage');
        $cache->addProperty('accounts');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $frontPage = Preferences::get('frontPageAccounts', $crud->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray());
        $accounts  = $crud->getAccountsById($frontPage->data);

        foreach ($accounts as $account) {
            $balances = [];
            $current  = clone $start;
            $range    = Steam::balanceInRange($account, $start, clone $end);
            $previous = round(array_values($range)[0], 2);
            while ($current <= $end) {
                $format     = $current->format('Y-m-d');
                $balance    = isset($range[$format]) ? round($range[$format], 2) : $previous;
                $previous   = $balance;
                $balances[] = $balance;
                $current->addDay();
            }
            $account->balances = $balances;
        }
        $data = $this->generator->frontpage($accounts, $start, $end);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for a given set of dates and accounts.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('all');
        $cache->addProperty('accounts');
        $cache->addProperty('default');
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        foreach ($accounts as $account) {
            $balances = [];
            $current  = clone $start;
            $range    = Steam::balanceInRange($account, $start, clone $end);
            $previous = round(array_values($range)[0], 2);
            while ($current <= $end) {
                $format     = $current->format('Y-m-d');
                $balance    = isset($range[$format]) ? round($range[$format], 2) : $previous;
                $previous   = $balance;
                $balances[] = $balance;
                $current->addDay();
            }
            $account->balances = $balances;
        }

        // make chart:
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
        $start = clone session('start', Carbon::now()->startOfMonth());
        $end   = clone session('end', Carbon::now()->endOfMonth());

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

        $format    = (string)trans('config.month_and_day');
        $range     = Steam::balanceInRange($account, $start, $end);
        $current   = clone $start;
        $previous  = array_values($range)[0];
        $labels    = [];
        $chartData = [];

        while ($end >= $current) {
            $theDate = $current->format('Y-m-d');
            $balance = $range[$theDate] ?? $previous;

            $labels[]    = $current->formatLocalized($format);
            $chartData[] = $balance;
            $previous    = $balance;
            $current->addDay();
        }


        $data = $this->generator->single($account, $labels, $chartData);
        $cache->store($data);

        return Response::json($data);
    }

}
