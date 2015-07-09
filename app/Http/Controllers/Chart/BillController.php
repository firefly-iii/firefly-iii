<?php

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Response;
use Session;

/**
 * Class BillController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class BillController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\Bill\BillChartGenerator */
    protected $generator;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\Bill\BillChartGenerator');
    }

    /**
     * Shows all bills and whether or not theyve been paid this month (pie chart).
     *
     * @param BillRepositoryInterface    $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(BillRepositoryInterface $repository)
    {
        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties(); // chart properties for cache:
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('bills');
        $cache->addProperty('frontpage');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $set = $repository->getBillsForChart($start, $end);

        // optionally expand this set with credit card data
        $set    = $repository->getCreditCardInfoForChart($set, $start, $end);
        $paid   = $set->get('paid');
        $unpaid = $set->get('unpaid');


        // build chart:
        $data = $this->generator->frontpage($paid, $unpaid);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the overview for a bill. The min/max amount and matched journals.
     *
     * @param BillRepositoryInterface $repository
     * @param Bill                    $bill
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function single(BillRepositoryInterface $repository, Bill $bill)
    {
        $cache = new CacheProperties;
        $cache->addProperty('single');
        $cache->addProperty('bill');
        $cache->addProperty($bill->id);
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        // get first transaction or today for start:
        $results = $repository->getJournals($bill);

        // resort:
        $results = $results->sortBy(
            function (TransactionJournal $journal) {
                return $journal->date->format('U');
            }
        );

        $data = $this->generator->single($bill, $results);
        $cache->store($data);

        return Response::json($data);
    }
}
