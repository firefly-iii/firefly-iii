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

use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Models\TransactionCurrency;
use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Chart\ChartRequest;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Chart\ChartData;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ApiSupport;
use FireflyIII\Support\Http\Api\CollectsAccountsFromFilter;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use ApiSupport;
    use CollectsAccountsFromFilter;

    private ChartData                  $chartData;
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
                $this->chartData  = new ChartData();
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

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
        $currency     = $this->repository->getAccountCurrency($account);
        if (!$currency instanceof TransactionCurrency) {
            $currency = $this->default;
        }
        $currentSet   = [
            'label'                   => $account->name,

            // the currency that belongs to the account.
            'currency_id'             => (string) $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,

            // the default currency of the user (could be the same!)
            'date'                    => $params['start']->toAtomString(),
            'start'                   => $params['start']->toAtomString(),
            'end'                     => $params['end']->toAtomString(),
            'period'                  => '1D',
            'entries'                 => [],
        ];
        $currentStart = clone $params['start'];
        $range        = Steam::finalAccountBalanceInRange($account, $params['start'], clone $params['end'], $this->convertToNative);

        $previous     = array_values($range)[0]['balance'];
        while ($currentStart <= $params['end']) {
            $format                        = $currentStart->format('Y-m-d');
            $label                         = $currentStart->toAtomString();
            $balance                       = array_key_exists($format, $range) ? $range[$format]['balance'] : $previous;
            $previous                      = $balance;

            $currentStart->addDay();
            $currentSet['entries'][$label] = $balance;
        }
        $this->chartData->add($currentSet);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/charts/getChartAccountOverview
     *
     * @throws ValidationException
     */
    public function overview(DateRequest $request): JsonResponse
    {
        // parameters for chart:
        $dates      = $request->getAll();

        /** @var Carbon $start */
        $start      = $dates['start'];

        /** @var Carbon $end */
        $end        = $dates['end'];

        // set dates to end of day + start of day:
        $start->startOfDay();
        $end->endOfDay();

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value])->pluck('id')->toArray();

        /** @var Preference $frontpage */
        $frontpage = Preferences::get('frontpageAccounts', $defaultSet);

        if (!(is_array($frontpage->data) && count($frontpage->data) > 0)) {
            $frontpage->data = $defaultSet;
            $frontpage->save();
        }

        // get accounts:
        $accounts   = $this->repository->getAccountsById($frontpage->data);
        $chartData  = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency     = $this->repository->getAccountCurrency($account) ?? $this->nativeCurrency;
            $field        = $this->convertToNative && $currency->id !== $this->nativeCurrency->id ? 'native_balance' : 'balance';
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
            $range        = Steam::finalAccountBalanceInRange($account, $start, clone $end, $this->convertToNative);
            $previous     = array_values($range)[0][$field];
            while ($currentStart <= $end) {
                $format                        = $currentStart->format('Y-m-d');
                $label                         = $currentStart->toAtomString();
                $balance                       = array_key_exists($format, $range) ? $range[$format][$field] : $previous;
                $previous                      = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[]  = $currentSet;
        }

        return response()->json($chartData);
    }
}
