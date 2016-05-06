<?php
declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Account\AccountChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Preferences;
use Response;

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
     * Shows the balances for a given set of dates and accounts.
     *
     * @param            $reportType
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function report(string $reportType, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('all');
        $cache->addProperty('accounts');
        $cache->addProperty('default');
        $cache->addProperty($reportType);
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        // make chart:
        $data = $this->generator->frontpage($accounts, $start, $end);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the balances for all the user's expense accounts.
     *
     * @param ARI $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function expenseAccounts(ARI $repository)
    {
        $start    = clone session('start', Carbon::now()->startOfMonth());
        $end      = clone session('end', Carbon::now()->endOfMonth());
        $accounts = $repository->getAccounts(['Expense account', 'Beneficiary account']);

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expenseAccounts');
        $cache->addProperty('accounts');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $data = $this->generator->expenseAccounts($accounts, $start, $end);
        $cache->store($data);

        return Response::json($data);

    }

    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param ARI $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(ARI $repository)
    {
        $frontPage = Preferences::get('frontPageAccounts', []);
        $start     = clone session('start', Carbon::now()->startOfMonth());
        $end       = clone session('end', Carbon::now()->endOfMonth());
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

        $data = $this->generator->single($account, $start, $end);
        $cache->store($data);

        return Response::json($data);
    }
}
