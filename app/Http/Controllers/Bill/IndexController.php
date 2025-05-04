<?php

/**
 * IndexController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\Bill;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\ObjectGroup\OrganisesObjectGroups;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    use OrganisesObjectGroups;

    private BillRepositoryInterface $repository;

    /**
     * BillController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string) trans('firefly.bills'));
                app('view')->share('mainTitleIcon', 'fa-calendar-o');
                $this->repository = app(BillRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show all bills.
     */
    public function index(): Application|Factory|\Illuminate\Contracts\Foundation\Application|View
    {
        $this->cleanupObjectGroups();
        $this->repository->correctOrder();
        $start       = session('start');
        $end         = session('end');
        $collection  = $this->repository->getBills();
        $total       = $collection->count();

        $parameters  = new ParameterBag();
        // sub one day from temp start so the last paid date is one day before it should be.
        $tempStart   = clone $start;
        // 2023-06-23 do not sub one day from temp start, fix is in BillTransformer::payDates instead
        // $tempStart->subDay();
        $parameters->set('start', $tempStart);
        $parameters->set('end', $end);
        $parameters->set('convertToNative', $this->convertToNative);
        $parameters->set('defaultCurrency', $this->defaultCurrency);

        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($parameters);

        // loop all bills, convert to array and add rules and stuff.
        $rules       = $this->repository->getRulesForBills($collection);

        // make bill groups:
        $bills       = [
            0 => [ // the index is the order, not the ID.
                'object_group_id'    => 0,
                'object_group_title' => (string) trans('firefly.default_group_title_name'),
                'bills'              => [],
            ],
        ];

        /** @var Bill $bill */
        foreach ($collection as $bill) {
            $array                            = $transformer->transform($bill);
            $groupOrder                       = (int) $array['object_group_order'];
            // make group array if necessary:
            $bills[$groupOrder] ??= [
                'object_group_id'    => $array['object_group_id'],
                'object_group_title' => $array['object_group_title'],
                'bills'              => [],
            ];

            $currency                         = $bill->transactionCurrency ?? $this->defaultCurrency;
            $array['currency_id']             = $currency->id;
            $array['currency_name']           = $currency->name;
            $array['currency_symbol']         = $currency->symbol;
            $array['currency_code']           = $currency->code;
            $array['currency_decimal_places'] = $currency->decimal_places;
            $array['attachments']             = $this->repository->getAttachments($bill);
            $array['rules']                   = $rules[$bill['id']] ?? [];
            $bills[$groupOrder]['bills'][]    = $array;
        }
        // order by key
        ksort($bills);

        // summarise per currency / per group.
        $sums        = $this->getSums($bills);
        $totals      = $this->getTotals($sums);
        $today       = now()->startOfDay();

        return view('bills.index', compact('bills', 'sums', 'total', 'totals', 'today'));
    }

    /**
     * @throws FireflyException
     */
    private function getSums(array $bills): array
    {
        $sums  = [];
        $range = app('navigation')->getViewRange(true);

        /** @var array $group */
        foreach ($bills as $groupOrder => $group) {
            /** @var array $bill */
            foreach ($group['bills'] as $bill) {
                if (false === $bill['active']) {
                    continue;
                }

                $currencyId                                   = $bill['currency_id'];
                $sums[$groupOrder][$currencyId] ??= [
                    'currency_id'             => $currencyId,
                    'currency_code'           => $bill['currency_code'],
                    'currency_name'           => $bill['currency_name'],
                    'currency_symbol'         => $bill['currency_symbol'],
                    'currency_decimal_places' => $bill['currency_decimal_places'],
                    'avg'                     => '0',
                    'period'                  => $range,
                    'per_period'              => '0',
                ];

                // only fill in avg when bill is active.
                if (null !== $bill['next_expected_match']) {
                    $avg                                   = bcdiv(bcadd((string) $bill['amount_min'], (string) $bill['amount_max']), '2');
                    $avg                                   = bcmul($avg, (string) count($bill['pay_dates']));
                    $sums[$groupOrder][$currencyId]['avg'] = bcadd($sums[$groupOrder][$currencyId]['avg'], $avg);
                }
                // fill in per period regardless:
                $sums[$groupOrder][$currencyId]['per_period'] = bcadd($sums[$groupOrder][$currencyId]['per_period'], $this->amountPerPeriod($bill, $range));
            }
        }

        return $sums;
    }

    private function amountPerPeriod(array $bill, string $range): string
    {
        $avg        = bcdiv(bcadd((string) $bill['amount_min'], (string) $bill['amount_max']), '2');

        app('log')->debug(sprintf('Amount per period for bill #%d "%s"', $bill['id'], $bill['name']));
        app('log')->debug(sprintf('Average is %s', $avg));
        // calculate amount per year:
        $multiplies = [
            'yearly'    => '1',
            'half-year' => '2',
            'quarterly' => '4',
            'monthly'   => '12',
            'weekly'    => '52.17',
            'daily'     => '365.24',
        ];
        $yearAmount = bcmul($avg, bcdiv($multiplies[$bill['repeat_freq']], (string) ($bill['skip'] + 1)));
        app('log')->debug(sprintf('Amount per year is %s (%s * %s / %s)', $yearAmount, $avg, $multiplies[$bill['repeat_freq']], (string) ($bill['skip'] + 1)));

        // per period:
        $division   = [
            '1Y'      => '1',
            '6M'      => '2',
            '3M'      => '4',
            '1M'      => '12',
            '1W'      => '52.16',
            '1D'      => '365.24',
            'YTD'     => '1',
            'QTD'     => '4',
            'MTD'     => '12',
            'last7'   => '52.16',
            'last30'  => '12',
            'last90'  => '4',
            'last365' => '1',
        ];
        $perPeriod  = bcdiv($yearAmount, $division[$range]);

        app('log')->debug(sprintf('Amount per %s is %s (%s / %s)', $range, $perPeriod, $yearAmount, $division[$range]));

        return $perPeriod;
    }

    private function getTotals(array $sums): array
    {
        $totals = [];
        if (count($sums) < 2) {
            return [];
        }

        /**
         * @var array $array
         */
        foreach ($sums as $array) {
            /**
             * @var int   $currencyId
             * @var array $entry
             */
            foreach ($array as $currencyId => $entry) {
                $totals[$currencyId] ??= [
                    'currency_id'             => $currencyId,
                    'currency_code'           => $entry['currency_code'],
                    'currency_name'           => $entry['currency_name'],
                    'currency_symbol'         => $entry['currency_symbol'],
                    'currency_decimal_places' => $entry['currency_decimal_places'],
                    'avg'                     => '0',
                    'period'                  => $entry['period'],
                    'per_period'              => '0',
                ];
                $totals[$currencyId]['avg']        = bcadd($totals[$currencyId]['avg'], (string) $entry['avg']);
                $totals[$currencyId]['per_period'] = bcadd($totals[$currencyId]['per_period'], (string) $entry['per_period']);
            }
        }

        return $totals;
    }

    /**
     * Set the order of a bill.
     */
    public function setOrder(Request $request, Bill $bill): JsonResponse
    {
        $objectGroupTitle = (string) $request->get('objectGroupTitle');
        $newOrder         = (int) $request->get('order');
        $this->repository->setOrder($bill, $newOrder);
        if ('' !== $objectGroupTitle) {
            $this->repository->setObjectGroup($bill, $objectGroupTitle);
        }
        if ('' === $objectGroupTitle) {
            $this->repository->removeObjectGroup($bill);
        }

        return response()->json(['data' => 'OK']);
    }
}
