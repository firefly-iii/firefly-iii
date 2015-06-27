<?php

namespace FireflyIII\Http\Controllers\Chart;

use App;
use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;
use Session;
use Steam;

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
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = App::make('FireflyIII\Generator\Chart\Bill\BillChartGenerator');
    }

    /**
     * Shows all bills and whether or not theyve been paid this month (pie chart).
     *
     * @param BillRepositoryInterface    $repository
     * @param AccountRepositoryInterface $accounts
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(BillRepositoryInterface $repository, AccountRepositoryInterface $accounts)
    {

        $start = Session::get('start', Carbon::now()->startOfMonth());
        $end   = Session::get('end', Carbon::now()->endOfMonth());


        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('bills');
        $cache->addProperty('frontpage');
        if ($cache->has()) {
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $bills  = $repository->getActiveBills();
        $paid   = new Collection; // journals.
        $unpaid = new Collection; // bills


        /** @var Bill $bill */
        foreach ($bills as $bill) {
            $ranges = $repository->getRanges($bill, $start, $end);

            foreach ($ranges as $range) {
                // paid a bill in this range?
                $journals = $repository->getJournalsInRange($bill, $range['start'], $range['end']);
                if ($journals->count() == 0) {
                    $unpaid->push([$bill, $range['start']]);
                } else {
                    $paid = $paid->merge($journals);
                }

            }
        }

        $creditCards = $accounts->getCreditCards();
        foreach ($creditCards as $creditCard) {
            $balance = Steam::balance($creditCard, $end, true);
            $date    = new Carbon($creditCard->getMeta('ccMonthlyPaymentDate'));
            if ($balance < 0) {
                // unpaid! create a fake bill that matches the amount.
                $description = $creditCard->name;
                $amount      = $balance * -1;
                $fakeBill    = $repository->createFakeBill($description, $date, $amount);
                unset($description, $amount);
                $unpaid->push([$fakeBill, $date]);
            }
            if ($balance == 0) {
                // find transfer(s) TO the credit card which should account for
                // anything paid. If not, the CC is not yet used.
                $journals = $accounts->getTransfersInRange($creditCard, $start, $end);
                $paid     = $paid->merge($journals);
            }
        }

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

        $data = $this->generator->single($bill, $results);
        $cache->store($data);

        return Response::json($data);
    }
}
