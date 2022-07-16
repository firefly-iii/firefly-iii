<?php
declare(strict_types=1);
/*
 * AccountController.php
 * Copyright (c) 2022 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ConvertsExchangeRates;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use ConvertsExchangeRates;

    private AccountRepositoryInterface $repository;

    /**
     *
     */
    public function __construct()
    {
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                return $next($request);
            }
        );
    }

    /**
     * @param DateRequest $request
     * @return JsonResponse
     */
    public function dashboard(DateRequest $request): JsonResponse
    {
        // parameters for chart:
        $dates = $request->getAll();
        /** @var Carbon $start */
        $start = $dates['start'];
        /** @var Carbon $end */
        $end = $dates['end'];

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT])->pluck('id')->toArray();
        $frontPage  = app('preferences')->get('frontPageAccounts', $defaultSet);
        $default    = app('amount')->getDefaultCurrency();
        $accounts   = $this->repository->getAccountsById($frontPage->data);
        $chartData  = [];

        if (empty($frontPage->data)) {
            $frontPage->data = $defaultSet;
            $frontPage->save();
        }

        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet   = [
                'label'                   => $account->name,
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'native_id'               => null,
                'native_code'             => null,
                'native_symbol'           => null,
                'native_decimal_places'   => null,
                'start_date'              => $start->toAtomString(),
                'end_date'                => $end->toAtomString(),
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [],
            ];
            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = round((float) array_values($range)[0], 12);
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->toAtomString();
                $balance  = array_key_exists($format, $range) ? round((float) $range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $currentSet = $this->cerChartSet($currentSet);
            $chartData[] = $currentSet;
        }

        return response()->json($chartData);
        return response()->json(['x']);
    }

}
