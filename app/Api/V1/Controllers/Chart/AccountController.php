<?php

/**
 * AccountController.php
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

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ApiSupport;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use ApiSupport;

    private AccountRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/charts/getChartAccountOverview
     *
     * @throws FireflyException
     */
    public function overview(DateRequest $request): JsonResponse
    {
        // parameters for chart:
        $dates      = $request->getAll();

        /** @var Carbon $start */
        $start      = $dates['start'];

        /** @var Carbon $end */
        $end        = $dates['end'];

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::ASSET])->pluck('id')->toArray();

        /** @var Preference $frontpage */
        $frontpage  = app('preferences')->get('frontpageAccounts', $defaultSet);
        $default    = app('amount')->getDefaultCurrency();

        if (!(is_array($frontpage->data) && count($frontpage->data) > 0)) {
            $frontpage->data = $defaultSet;
            $frontpage->save();
        }

        // get accounts:
        $accounts   = $this->repository->getAccountsById($frontpage->data);
        $chartData  = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency     = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet   = [
                'label'                   => $account->name,
                'currency_id'             => (string) $currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'start_date'              => $start->toAtomString(),
                'end_date'                => $end->toAtomString(),
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [],
            ];
            // TODO this code is also present in the V2 chart account controller so this method is due to be deprecated.
            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            // 2022-10-11 this method no longer converts to float.
            $previous     = array_values($range)[0];
            while ($currentStart <= $end) {
                $format                        = $currentStart->format('Y-m-d');
                $label                         = $currentStart->toAtomString();
                $balance                       = array_key_exists($format, $range) ? $range[$format] : $previous;
                $previous                      = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[]  = $currentSet;
        }

        return response()->json($chartData);
    }
}
