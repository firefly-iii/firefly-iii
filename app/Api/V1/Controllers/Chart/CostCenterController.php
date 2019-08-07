<?php

/**
 * CostCenterController.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\CostCenter\CostCenterRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Class CostCenterController
 */
class CostCenterController extends Controller
{
    /** @var CostCenterRepositoryInterface */
    private $costCenterRepository;

    /**
     * CostCenterController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                       = auth()->user();
                $this->costCenterRepository = app(CostCenterRepositoryInterface::class);
                $this->costCenterRepository->setUser($user);

                return $next($request);
            }
        );
    }


    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function overview(Request $request): JsonResponse
    {
        // parameters for chart:
        $start = (string)$request->get('start');
        $end   = (string)$request->get('end');
        if ('' === $start || '' === $end) {
            throw new FireflyException('Start and end are mandatory parameters.');
        }
        $start      = Carbon::createFromFormat('Y-m-d', $start);
        $end        = Carbon::createFromFormat('Y-m-d', $end);
        $tempData   = [];
        $spent      = $this->costCenterRepository->spentInPeriodPcWoCostCenter(new Collection, new Collection, $start, $end);
        $earned     = $this->costCenterRepository->earnedInPeriodPerCurrency(new Collection, new Collection, $start, $end);
        $costCenters = [];

        // earned:
        foreach ($earned as $costCenterId => $row) {
            $costCenterName = $row['name'];
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
                $costCenters[$costCenterName] = isset($costCenters[$costCenterName]) ? $costCenters[$costCenterName] + $amount : $amount;
                $tempData[$key]['entries'][$costCenterName]
                                           = $amount;

            }
        }

        // earned with no cost center:
        $noCostCenter = $this->costCenterRepository->earnedInPeriodPcWoCostCenter(new Collection, $start, $end);
        foreach ($noCostCenter as $currencyId => $income) {
            $costCenterName = (string)trans('firefly.no_cost_center');
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
            $amount                    = round($income['spent'], $decimalPlaces);
            $costCenters[$costCenterName] = isset($costCenters[$costCenterName]) ? $costCenters[$costCenterName] + $amount : $amount;
            $tempData[$key]['entries'][$costCenterName]
                                       = $amount;
        }


        // spent
        foreach ($spent as $costCenterId => $row) {
            $costCenterName = $row['name'];
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
                $costCenters[$costCenterName] = isset($costCenters[$costCenterName]) ? $costCenters[$costCenterName] + $amount : $amount;
                $tempData[$key]['entries'][$costCenterName]
                                           = $amount;

            }
        }

        // spent with no cost center
        $noCostCenter = $this->costCenterRepository->spentInPeriodPcWoCostCenter(new Collection, $start, $end);
        foreach ($noCostCenter as $currencyId => $expense) {
            $costCenterName = (string)trans('firefly.no_cost_center');
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
            $costCenters[$costCenterName] = isset($costCenters[$costCenterName]) ? $costCenters[$costCenterName] + $amount : $amount;
            $tempData[$key]['entries'][$costCenterName]
                                       = $amount;
        }


        asort($costCenters);
        $keys = array_keys($costCenters);

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
