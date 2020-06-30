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


use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Transformers\BillTransformer;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    private BillRepositoryInterface $repository;

    /**
     * BillController constructor.
     *
     * @codeCoverageIgnore
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
    public function index()
    {
        $start      = session('start');
        $end        = session('end');
        $collection = $this->repository->getBills();
        $total      = $collection->count();

        $defaultCurrency = app('amount')->getDefaultCurrency();
        $parameters      = new ParameterBag;
        $parameters->set('start', $start);
        $parameters->set('end', $end);

        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($parameters);

        // loop all bills, convert to array and add rules and stuff.
        $rules = $this->repository->getRulesForBills($collection);

        // make bill groups:
        $bills = [
            0 => [ // the index is the order, not the ID.
                   'object_group_id'    => 0,
                   'object_group_title' => (string) trans('firefly.default_group_title_name'),
                   'bills'              => [],
            ],
        ];


        /** @var Bill $bill */
        foreach ($collection as $bill) {
            $array      = $transformer->transform($bill);
            $groupOrder = (int) $array['object_group_order'];
            // make group array if necessary:
            $bills[$groupOrder] = $bills[$groupOrder] ?? [
                    'object_group_id'    => $array['object_group_id'],
                    'object_group_title' => $array['object_group_title'],
                    'bills'              => [],
                ];

            $nextExpectedMatch                 = new Carbon($array['next_expected_match']);
            $array['next_expected_match_diff'] = $nextExpectedMatch->isToday()
                ? trans('firefly.today')
                : $nextExpectedMatch->diffForHumans(
                    today(), Carbon::DIFF_RELATIVE_TO_NOW
                );
            $currency                          = $bill->transactionCurrency ?? $defaultCurrency;
            $array['currency_id']              = $currency->id;
            $array['currency_name']            = $currency->name;
            $array['currency_symbol']          = $currency->symbol;
            $array['currency_code']            = $currency->code;
            $array['currency_decimal_places']  = $currency->decimal_places;
            $array['attachments']              = $this->repository->getAttachments($bill);
            $array['rules']                    = $rules[$bill['id']] ?? [];
            $bills[$groupOrder]['bills'][]     = $array;
        }

        // order by key
        ksort($bills);

        // summarise per currency / per group.
        $sums = $this->getSums($bills);

        return view('bills.index', compact('bills', 'sums', 'total'));
    }


    /**
     * @param array $bills
     *
     * @return array
     */
    private function getSums(array $bills): array
    {
        $sums = [];

        /** @var array $group */
        foreach ($bills as $groupOrder => $group) {
            /** @var array $bill */
            foreach ($group['bills'] as $bill) {
                if (false === $bill['active']) {
                    continue;
                }
                if (0 === count($bill['pay_dates'])) {
                    continue;
                }
                /** @var TransactionCurrency $currency */
                $currencyId                     = $bill['currency_id'];
                $sums[$groupOrder][$currencyId] = $sums[$groupOrder][$currencyId] ?? [
                        'currency_id'             => $currencyId,
                        'currency_code'           => $bill['currency_code'],
                        'currency_name'           => $bill['currency_name'],
                        'currency_symbol'         => $bill['currency_symbol'],
                        'currency_decimal_places' => $bill['currency_decimal_places'],
                        'avg'                     => '0',
                    ];

                $avg                                   = bcdiv(bcadd((string) $bill['amount_min'], (string) $bill['amount_max']), '2');
                $avg                                   = bcmul($avg, (string) count($bill['pay_dates']));
                $sums[$groupOrder][$currencyId]['avg'] = bcadd($sums[$groupOrder][$currencyId]['avg'], $avg);
            }
        }

        return $sums;
    }
}
