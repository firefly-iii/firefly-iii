<?php

/**
 * CategoryController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class CategoryController
 */
class CategoryController extends Controller
{
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;
    /** @var NoCategoryRepositoryInterface */
    private $noCatRepository;
    /** @var OperationsRepositoryInterface */
    private $opsRepository;

    /**
     * AccountController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                     = auth()->user();
                $this->categoryRepository = app(CategoryRepositoryInterface::class);
                $this->opsRepository      = app(OperationsRepositoryInterface::class);
                $this->noCatRepository    = app(NoCategoryRepositoryInterface::class);
                $this->categoryRepository->setUser($user);
                $this->opsRepository->setUser($user);
                $this->noCatRepository->setUser($user);

                return $next($request);
            }
        );
    }


    /**
     * @param DateRequest $request
     *
     * @return JsonResponse
     *
     * TODO after 4.8,0, simplify
     */
    public function overview(DateRequest $request): JsonResponse
    {
        // parameters for chart:
        $dates = $request->getAll();
        /** @var Carbon $start */
        $start = $dates['start'];
        /** @var Carbon $end */
        $end = $dates['end'];


        $tempData      = [];
        $spentWith     = $this->opsRepository->listExpenses($start, $end);
        $earnedWith    = $this->opsRepository->listIncome($start, $end);
        $spentWithout  = $this->noCatRepository->listExpenses($start, $end);
        $earnedWithout = $this->noCatRepository->listIncome($start, $end);
        $categories    = [];


        foreach ([$spentWith, $earnedWith] as $set) {
            foreach ($set as $currency) {
                foreach ($currency['categories'] as $category) {
                    $categories[] = $category['name'];
                    $inKey        = sprintf('%d-i', $currency['currency_id']);
                    $outKey       = sprintf('%d-e', $currency['currency_id']);
                    // make data arrays if not yet present.
                    $tempData[$inKey]  = $tempData[$inKey] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'label'                   => (string)trans('firefly.box_earned_in_currency', ['currency' => $currency['currency_name']]),
                            'currency_code'           => $currency['currency_code'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'type'                    => 'bar', // line, area or bar
                            'yAxisID'                 => 0, // 0, 1, 2
                            'entries'                 => [
                                // per category:
                                // "category" => 5,
                            ],
                        ];
                    $tempData[$outKey] = $tempData[$outKey] ?? [
                            'currency_id'             => $currency['currency_id'],
                            'label'                   => (string)trans('firefly.box_spent_in_currency', ['currency' => $currency['currency_name']]),
                            'currency_code'           => $currency['currency_code'],
                            'currency_symbol'         => $currency['currency_symbol'],
                            'currency_decimal_places' => $currency['currency_decimal_places'],
                            'type'                    => 'bar', // line, area or bar
                            'yAxisID'                 => 0, // 0, 1, 2
                            'entries'                 => [
                                // per category:
                                // "category" => 5,
                            ],
                        ];

                    foreach ($category['transaction_journals'] as $journal) {
                        // is it expense or income?
                        $letter                                  = -1 === bccomp($journal['amount'], '0') ? 'e' : 'i';
                        $currentKey                              = sprintf('%d-%s', $currency['currency_id'], $letter);
                        $name                                    = $category['name'];
                        $tempData[$currentKey]['entries'][$name] = $tempData[$currentKey]['entries'][$name] ?? '0';
                        $tempData[$currentKey]['entries'][$name] = bcadd($tempData[$currentKey]['entries'][$name], $journal['amount']);
                    }
                }
            }
        }

        foreach ([$spentWithout, $earnedWithout] as $set) {
            foreach ($set as $currency) {
                $inKey        = sprintf('%d-i', $currency['currency_id']);
                $outKey       = sprintf('%d-e', $currency['currency_id']);
                $categories[] = (string)trans('firefly.no_category');
                // make data arrays if not yet present.
                $tempData[$inKey]  = $tempData[$inKey] ?? [
                        'currency_id'             => $currency['currency_id'],
                        'label'                   => (string)trans('firefly.box_earned_in_currency', ['currency' => $currency['currency_name']]),
                        'currency_code'           => $currency['currency_code'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'type'                    => 'bar', // line, area or bar
                        'yAxisID'                 => 0, // 0, 1, 2
                        'entries'                 => [
                            // per category:
                            // "category" => 5,
                        ],
                    ];
                $tempData[$outKey] = $tempData[$outKey] ?? [
                        'currency_id'             => $currency['currency_id'],
                        'label'                   => (string)trans('firefly.box_spent_in_currency', ['currency' => $currency['currency_name']]),
                        'currency_code'           => $currency['currency_code'],
                        'currency_symbol'         => $currency['currency_symbol'],
                        'currency_decimal_places' => $currency['currency_decimal_places'],
                        'type'                    => 'bar', // line, area or bar
                        'yAxisID'                 => 0, // 0, 1, 2
                        'entries'                 => [
                            // per category:
                            // "category" => 5,
                        ],
                    ];
                foreach ($currency['transaction_journals'] as $journal) {
                    // is it expense or income?
                    $letter                                  = -1 === bccomp($journal['amount'], '0') ? 'e' : 'i';
                    $currentKey                              = sprintf('%d-%s', $currency['currency_id'], $letter);
                    $name                                    = (string)trans('firefly.no_category');
                    $tempData[$currentKey]['entries'][$name] = $tempData[$currentKey]['entries'][$name] ?? '0';
                    $tempData[$currentKey]['entries'][$name] = bcadd($tempData[$currentKey]['entries'][$name], $journal['amount']);
                }
            }
        }

        // re-sort every spent array and add 0 for missing entries.
        foreach ($tempData as $index => $set) {
            $oldSet = $set['entries'];
            $newSet = [];
            foreach ($categories as $category) {
                $value             = $oldSet[$category] ?? '0';
                $value             = -1 === bccomp($value, '0') ? bcmul($value, '-1') : $value;
                $newSet[$category] = $value;
            }
            $tempData[$index]['entries'] = $newSet;
        }
        $chartData = array_values($tempData);

        return response()->json($chartData);
    }
}
