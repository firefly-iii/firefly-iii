<?php

/**
 * CategoryController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class CategoryController
 */
class CategoryController extends Controller
{
    /** @var CategoryRepositoryInterface */
    private $categoryRepository;

    /**
     * AccountController constructor.
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
                $this->categoryRepository->setUser($user);

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


        $tempData   = [];
        $spent      = $this->categoryRepository->spentInPeriodPerCurrency(new Collection, new Collection, $start, $end);
        $earned     = $this->categoryRepository->earnedInPeriodPerCurrency(new Collection, new Collection, $start, $end);
        $categories = [];

        // earned:
        foreach ($earned as $categoryId => $row) {
            $categoryName = $row['name'];
            foreach ($row['earned'] as $currencyId => $income) {
                // find or make set for currency:
                $key           = sprintf('%s-e', $currencyId);
                $decimalPlaces = $income['currency_decimal_places'];
                if (!isset($tempData[$key])) {
                    $tempData[$key] = [
                        'label'                   => (string)trans('firefly.box_earned_in_currency', ['currency' => $income['currency_symbol']]),
                        'currency_id'             => $income['currency_id'],
                        'currency_code'           => $income['currency_code'],
                        'currency_symbol'         => $income['currency_symbol'],
                        'currency_decimal_places' => $decimalPlaces,
                        'type'                    => 'bar', // line, area or bar
                        'yAxisID'                 => 0, // 0, 1, 2
                        'entries'                 => [],
                    ];
                }
                $amount                    = round($income['earned'], $decimalPlaces);
                $categories[$categoryName] = isset($categories[$categoryName]) ? $categories[$categoryName] + $amount : $amount;
                $tempData[$key]['entries'][$categoryName]
                                           = $amount;

            }
        }

        // earned with no category:
        $noCategory = $this->categoryRepository->earnedInPeriodPcWoCategory(new Collection, $start, $end);
        foreach ($noCategory as $currencyId => $income) {
            $categoryName = (string)trans('firefly.no_category');
            // find or make set for currency:
            $key           = sprintf('%s-e', $currencyId);
            $decimalPlaces = $income['currency_decimal_places'];
            if (!isset($tempData[$key])) {
                $tempData[$key] = [
                    'label'                   => (string)trans('firefly.box_earned_in_currency', ['currency' => $income['currency_symbol']]),
                    'currency_id'             => $income['currency_id'],
                    'currency_code'           => $income['currency_code'],
                    'currency_symbol'         => $income['currency_symbol'],
                    'currency_decimal_places' => $decimalPlaces,
                    'type'                    => 'bar', // line, area or bar
                    'yAxisID'                 => 0, // 0, 1, 2
                    'entries'                 => [],
                ];
            }
            $amount                    = round($income['earned'], $decimalPlaces);
            $categories[$categoryName] = isset($categories[$categoryName]) ? $categories[$categoryName] + $amount : $amount;
            $tempData[$key]['entries'][$categoryName]
                                       = $amount;
        }


        // spent
        foreach ($spent as $categoryId => $row) {
            $categoryName = $row['name'];
            // create a new set if necessary, "spent (EUR)":
            foreach ($row['spent'] as $currencyId => $expense) {
                // find or make set for currency:
                $key           = sprintf('%s-s', $currencyId);
                $decimalPlaces = $expense['currency_decimal_places'];
                if (!isset($tempData[$key])) {
                    $tempData[$key] = [
                        'label'                   => (string)trans('firefly.box_spent_in_currency', ['currency' => $expense['currency_symbol']]),
                        'currency_id'             => $expense['currency_id'],
                        'currency_code'           => $expense['currency_code'],
                        'currency_symbol'         => $expense['currency_symbol'],
                        'currency_decimal_places' => $decimalPlaces,
                        'type'                    => 'bar', // line, area or bar
                        'yAxisID'                 => 0, // 0, 1, 2
                        'entries'                 => [],
                    ];
                }
                $amount                    = round($expense['spent'], $decimalPlaces);
                $categories[$categoryName] = isset($categories[$categoryName]) ? $categories[$categoryName] + $amount : $amount;
                $tempData[$key]['entries'][$categoryName]
                                           = $amount;

            }
        }

        // spent with no category
        $noCategory = $this->categoryRepository->spentInPeriodPcWoCategory(new Collection, $start, $end);
        foreach ($noCategory as $currencyId => $expense) {
            $categoryName = (string)trans('firefly.no_category');
            // find or make set for currency:
            $key           = sprintf('%s-s', $currencyId);
            $decimalPlaces = $expense['currency_decimal_places'];
            if (!isset($tempData[$key])) {
                $tempData[$key] = [
                    'label'                   => (string)trans('firefly.box_spent_in_currency', ['currency' => $expense['currency_symbol']]),
                    'currency_id'             => $expense['currency_id'],
                    'currency_code'           => $expense['currency_code'],
                    'currency_symbol'         => $expense['currency_symbol'],
                    'currency_decimal_places' => $decimalPlaces,
                    'type'                    => 'bar', // line, area or bar
                    'yAxisID'                 => 0, // 0, 1, 2
                    'entries'                 => [],
                ];
            }
            $amount                    = round($expense['spent'], $decimalPlaces);
            $categories[$categoryName] = isset($categories[$categoryName]) ? $categories[$categoryName] + $amount : $amount;
            $tempData[$key]['entries'][$categoryName]
                                       = $amount;
        }


        asort($categories);
        $keys = array_keys($categories);

        // re-sort every spent array and add 0 for missing entries.
        foreach ($tempData as $index => $set) {
            $oldSet = $set['entries'];
            $newSet = [];
            foreach ($keys as $key) {
                $value        = $oldSet[$key] ?? 0;
                $value        = $value < 0 ? $value * -1 : $value;
                $newSet[$key] = $value;
            }
            $tempData[$index]['entries'] = $newSet;
        }
        $chartData = array_values($tempData);

        return response()->json($chartData);
    }
}
