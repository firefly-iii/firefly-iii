<?php

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
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
        $this->middleware('auth');
        parent::__construct();
        // create chart generator:
        $this->generator = app('FireflyIII\Generator\Chart\Bill\BillChartGenerator');
    }

    /**
     * Shows all bills and whether or not they've been paid this month (pie chart).
     *
     * @param BillRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(BillRepositoryInterface $repository)
    {
        $start         = Session::get('start', Carbon::now()->startOfMonth());
        $end           = Session::get('end', Carbon::now()->endOfMonth());
        $paid          = $repository->getBillsPaidInRange($start, $end); // will be a negative amount.
        $unpaid        = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $creditCardDue = $repository->getCreditCardBill($start, $end);

        if ($creditCardDue < 0) {
            // expenses are negative (bill not yet paid),
            $creditCardDue = bcmul($creditCardDue, '-1');
            $unpaid        = bcadd($unpaid, $creditCardDue);
        } else {
            // if more than zero, the bill has been paid: (transfer = positive).
            // amount must be negative to be added to $paid:
            $paid = bcadd($paid, $creditCardDue);
        }

        // build chart:
        $data = $this->generator->frontpage($paid, $unpaid);

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
