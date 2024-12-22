<?php

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

declare(strict_types=1);

namespace FireflyIII\Api\V2\Controllers\Chart;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Chart\ChartRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Support\Chart\ChartData;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\CollectsAccountsFromFilter;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use CleansChartData;
    use CollectsAccountsFromFilter;
    use ValidatesUserGroupTrait;

    private ChartData                  $chartData;
    private TransactionCurrency        $default;
    private AccountRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));
                $this->chartData = new ChartData();
                $this->default   = app('amount')->getDefaultCurrency();

                return $next($request);
            }
        );
    }

    /**
     * TODO fix documentation
     *
     * @throws FireflyException
     */
    public function dashboard(ChartRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $accounts        = $this->getAccountList($queryParameters);

        // move date to end of day
        $queryParameters['start']->startOfDay();
        $queryParameters['end']->endOfDay();

        // loop each account, and collect info:
        /** @var Account $account */
        foreach ($accounts as $account) {
            $this->renderAccountData($queryParameters, $account);
        }

        return response()->json($this->chartData->render());
    }

    /**
     * @throws FireflyException
     */
    private function renderAccountData(array $params, Account $account): void
    {
        $currency = $this->repository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = $this->default;
        }
        $currentSet   = [
            'label'                          => $account->name,

            // the currency that belongs to the account.
            'currency_id'                    => (string) $currency->id,
            'currency_code'                  => $currency->code,
            'currency_symbol'                => $currency->symbol,
            'currency_decimal_places'        => $currency->decimal_places,

            // the default currency of the user (could be the same!)
            'native_currency_id'             => (string) $this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,
            'date'                           => $params['start']->toAtomString(),
            'start'                          => $params['start']->toAtomString(),
            'end'                            => $params['end']->toAtomString(),
            'period'                         => '1D',
            'entries'                        => [],
            'native_entries'                 => [],
        ];
        $currentStart = clone $params['start'];
        $range        = app('steam')->finalAccountBalanceInRange($account, $params['start'], clone $params['end'], $currency);

        $previous       = array_values($range)[0]['balance'];
        $previousNative = array_values($range)[0]['native_balance'];
        while ($currentStart <= $params['end']) {
            $format         = $currentStart->format('Y-m-d');
            $label          = $currentStart->toAtomString();
            $balance        = array_key_exists($format, $range) ? $range[$format]['balance'] : $previous;
            $balanceNative  = array_key_exists($format, $range) ? $range[$format]['balance_native'] : $previousNative;
            $previous       = $balance;
            $previousNative = $balanceNative;

            $currentStart->addDay();
            $currentSet['entries'][$label]        = $balance;
            $currentSet['native_entries'][$label] = $balanceNative;
        }
        $this->chartData->add($currentSet);
    }
}
