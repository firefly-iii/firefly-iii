<?php
/**
 * BillController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Response;

/**
 * Class BillController.
 */
class BillController extends Controller
{
    /** @var GeneratorInterface */
    protected $generator;

    /**
     * checked.
     */
    public function __construct()
    {
        parent::__construct();
        $this->generator = app(GeneratorInterface::class);
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
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $paid      = $repository->getBillsPaidInRange($start, $end); // will be a negative amount.
        $unpaid    = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $chartData = [
            strval(trans('firefly.unpaid')) => $unpaid,
            strval(trans('firefly.paid'))   => $paid,
        ];

        $data = $this->generator->pieChart($chartData);
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
            return Response::json($cache->get()); // @codeCoverageIgnore
        }

        $results   = $collector->setAllAssetAccounts()->setBills(new Collection([$bill]))->getJournals();
        $results   = $results->sortBy(
            function (Transaction $transaction) {
                return $transaction->date->format('U');
            }
        );
        $chartData = [
            ['type' => 'bar', 'label' => trans('firefly.min-amount'), 'entries' => []],
            ['type' => 'bar', 'label' => trans('firefly.max-amount'), 'entries' => []],
            ['type' => 'line', 'label' => trans('firefly.journal-amount'), 'entries' => []],
        ];

        /** @var Transaction $entry */
        foreach ($results as $entry) {
            $date                           = $entry->date->formatLocalized(strval(trans('config.month_and_day')));
            $chartData[0]['entries'][$date] = $bill->amount_min; // minimum amount of bill
            $chartData[1]['entries'][$date] = $bill->amount_max; // maximum amount of bill
            $chartData[2]['entries'][$date] = bcmul($entry->transaction_amount, '-1'); // amount of journal
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return Response::json($data);
    }
}
