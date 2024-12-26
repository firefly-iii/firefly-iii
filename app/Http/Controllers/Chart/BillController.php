<?php

/**
 * BillController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\JsonResponse;

/**
 * Class BillController.
 */
class BillController extends Controller
{
    protected GeneratorInterface $generator;

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * Shows all bills and whether or not they've been paid this month (pie chart).
     */
    public function frontpage(BillRepositoryInterface $repository): JsonResponse
    {
        $start     = session('start', today(config('app.timezone'))->startOfMonth());
        $end       = session('end', today(config('app.timezone'))->endOfMonth());
        $cache     = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.bill.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get());
        }

        $chartData = [];
        $paid      = $repository->sumPaidInRange($start, $end);
        $unpaid    = $repository->sumUnpaidInRange($start, $end);

        /**
         * @var array $info
         */
        foreach ($paid as $info) {
            $amount            = $info['sum'];
            $label             = (string) trans('firefly.paid_in_currency', ['currency' => $info['name']]);
            $chartData[$label] = [
                'amount'          => $amount,
                'currency_symbol' => $info['symbol'],
                'currency_code'   => $info['code'],
            ];
        }

        /**
         * @var array $info
         */
        foreach ($unpaid as $info) {
            $amount            = $info['sum'];
            $label             = (string) trans('firefly.unpaid_in_currency', ['currency' => $info['name']]);
            $chartData[$label] = [
                'amount'          => $amount,
                'currency_symbol' => $info['symbol'],
                'currency_code'   => $info['code'],
            ];
        }

        $data      = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows overview for a single bill.
     *
     * @throws FireflyException
     */
    public function single(Bill $bill): JsonResponse
    {
        $cache      = new CacheProperties();
        $cache->addProperty('chart.bill.single');
        $cache->addProperty($bill->id);
        $cache->addProperty($this->convertToNative);
        if ($cache->has()) {
            // return response()->json($cache->get());
        }
        $locale     = app('steam')->getLocale();

        /** @var GroupCollectorInterface $collector */
        $collector  = app(GroupCollectorInterface::class);
        $journals   = $collector->setBill($bill)->getExtractedJournals();

        // sort the other way around:
        usort(
            $journals,
            static function (array $left, array $right) {
                if ($left['date']->gt($right['date'])) {
                    return 1;
                }
                if ($left['date']->lt($right['date'])) {
                    return -1;
                }

                return 0;
            }
        );
        $currency = $bill->transactionCurrency;
        if($this->convertToNative) {
            $currency = $this->defaultCurrency;
        }

        $chartData  = [
            [
                'type'            => 'line',
                'label'           => (string) trans('firefly.min-amount'),
                'currency_symbol' => $currency->symbol,
                'currency_code'   => $currency->code,
                'entries'         => [],
            ],
            [
                'type'            => 'line',
                'label'           => (string) trans('firefly.max-amount'),
                'currency_symbol' => $currency->symbol,
                'currency_code'   => $currency->code,
                'entries'         => [],
            ],
            [
                'type'            => 'bar',
                'label'           => (string) trans('firefly.journal-amount'),
                'currency_symbol' => $currency->symbol,
                'currency_code'   => $currency->code,
                'entries'         => [],
            ],
        ];
        $currencyId = $bill->transaction_currency_id;
        $amountMin = $bill->amount_min;
        $amountMax = $bill->amount_max;
        if($this->convertToNative) {
            $amountMin = $bill->native_amount_min;
            $amountMax = $bill->native_amount_max;
        }
        foreach ($journals as $journal) {
            $date                           = $journal['date']->isoFormat((string) trans('config.month_and_day_js', [], $locale));
            $chartData[0]['entries'][$date] = $amountMin; // minimum amount of bill
            $chartData[1]['entries'][$date] = $amountMax; // maximum amount of bill

            // append amount because there are more than one per moment:
            if (!array_key_exists($date, $chartData[2]['entries'])) {
                $chartData[2]['entries'][$date] = '0';
            }
            $amount                         = bcmul($journal['amount'], '-1');
            if($this->convertToNative) {
                $amount                         = bcmul($journal['native_amount'], '-1');
            }
            if($this->convertToNative && $currencyId === $journal['foreign_currency_id']) {
                $amount = bcmul($journal['foreign_amount'], '-1');
            }

            $chartData[2]['entries'][$date] = bcadd($chartData[2]['entries'][$date], $amount);  // amount of journal
        }

        $data       = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }
}
