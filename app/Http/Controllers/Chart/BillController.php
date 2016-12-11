<?php
/**
 * BillController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Generator\Chart\Bill\BillChartGeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;

/**
 * Class BillController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class BillController extends Controller
{

    /** @var  \FireflyIII\Generator\Chart\Bill\BillChartGeneratorInterface */
    protected $generator;

    /**
     * checked
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(BillChartGeneratorInterface::class);
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
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.bill.frontpage');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $paid      = $repository->getBillsPaidInRange($start, $end); // will be a negative amount.
        $unpaid    = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $chartData = [
            strval(trans('firefly.unpaid')) => $unpaid,
            strval(trans('firefly.paid'))   => $paid,
        ];

        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->pieChart($chartData);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param JournalCollectorInterface $collector
     * @param Bill                      $bill
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function single(JournalCollectorInterface $collector, Bill $bill)
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.bill.single');
        $cache->addProperty($bill->id);
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $results = $collector->setAllAssetAccounts()->setBills(new Collection([$bill]))->getJournals();
        $results = $results->sortBy(
            function (Transaction $transaction) {
                return $transaction->date->format('U');
            }
        );

        $chartData = [
            [
                'type'    => 'bar',
                'label'   => trans('firefly.min-amount'),
                'entries' => [],
            ],
            [
                'type'    => 'bar',
                'label'   => trans('firefly.max-amount'),
                'entries' => [],
            ],
            [
                'type'    => 'line',
                'label'   => trans('firefly.journal-amount'),
                'entries' => [],
            ],
        ];

        /** @var Transaction $entry */
        foreach ($results as $entry) {
            $date = $entry->date->formatLocalized(strval(trans('config.month_and_day')));
            // minimum amount of bill:
            $chartData[0]['entries'][$date] = $bill->amount_min;
            // maximum amount of bill:
            $chartData[1]['entries'][$date] = $bill->amount_min;
            // amount of journal:
            $chartData[2]['entries'][$date] = bcmul($entry->transaction_amount, '-1');
        }

        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);
        $data      = $generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }
}
