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
use FireflyIII\Api\V1\Requests\Chart\ChartRequest;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Exceptions\ValidationException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Chart\ChartData;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ApiSupport;
use FireflyIII\Support\Http\Api\CollectsAccountsFromFilter;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
        Log::debug(sprintf('dashboard(), convert to primary: %s', var_export($this->convertToPrimary, true)));

        // loop each account, and collect info:
        /** @var Account $account */
        foreach ($accounts as $account) {
            Log::debug(sprintf('Account #%d ("%s")', $account->id, $account->name));
            $this->renderAccountData($queryParameters, $account);
        }

        return response()->json($this->chartData->render());
    }

    /**
     * @throws FireflyException
     */
    private function renderAccountData(array $params, Account $account): void
    {
        Log::debug(sprintf('Now in %s(array, #%d)', __METHOD__, $account->id));
        $currency     = $this->repository->getAccountCurrency($account);
        $currentStart = clone $params['start'];
        $range        = Steam::finalAccountBalanceInRange($account, $params['start'], clone $params['end'], $this->convertToPrimary);


        $previous     = array_values($range)[0]['balance'];
        $pcPrevious   = null;
        if (!$currency instanceof TransactionCurrency) {
            $currency = $this->default;
        }
        $currentSet   = [
            'label'                   => $account->name,

            // the currency that belongs to the account.
            'currency_id'             => (string)$currency->id,
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
        if ($this->convertToPrimary) {
            $currentSet['pc_entries']                      = [];
            $currentSet['primary_currency_id']             = (string)$this->primaryCurrency->id;
            $currentSet['primary_currency_code']           = $this->primaryCurrency->code;
            $currentSet['primary_currency_symbol']         = $this->primaryCurrency->symbol;
            $currentSet['primary_currency_decimal_places'] = $this->primaryCurrency->decimal_places;
            $pcPrevious                                    = array_values($range)[0]['pc_balance'];
        }


        while ($currentStart <= $params['end']) {
            $format                        = $currentStart->format('Y-m-d');
            $label                         = $currentStart->toAtomString();
            $balance                       = array_key_exists($format, $range) ? $range[$format]['balance'] : $previous;
            $previous                      = $balance;
            $currentSet['entries'][$label] = $balance;


            // do the same for the primary currency balance, if relevant:
            $pcBalance                     = null;
            if ($this->convertToPrimary) {
                $pcBalance                        = array_key_exists($format, $range) ? $range[$format]['pc_balance'] : $pcPrevious;
                $pcPrevious                       = $pcBalance;
                $currentSet['pc_entries'][$label] = $pcBalance;
            }

            $currentStart->addDay();
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
        $dates        = $request->getAll();


        /** @var Carbon $start */
        $start        = $dates['start'];

        /** @var Carbon $end */
        $end          = $dates['end'];

        // set dates to end of day + start of day:
        $start->startOfDay();
        $end->endOfDay();

        $frontPageIds = $this->getFrontPageAccountIds();
        $accounts     = $this->repository->getAccountsById($frontPageIds);
        $chartData    = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            Log::debug(sprintf('Rendering chart data for account %s (%d)', $account->name, $account->id));
            $currency     = $this->repository->getAccountCurrency($account) ?? $this->primaryCurrency;
            $currentStart = clone $start;
            $range        = Steam::finalAccountBalanceInRange($account, $start, clone $end, $this->convertToPrimary);
            $previous     = array_values($range)[0]['balance'];
            $pcPrevious   = null;
            $currentSet   = [
                'label'                   => $account->name,
                'currency_id'             => (string)$currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'start_date'              => $start->toAtomString(),
                'end_date'                => $end->toAtomString(),
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [],
            ];

            // add "pc_entries" if convertToPrimary is true:
            if ($this->convertToPrimary) {
                $currentSet['pc_entries']                      = [];
                $currentSet['primary_currency_id']             = (string)$this->primaryCurrency->id;
                $currentSet['primary_currency_code']           = $this->primaryCurrency->code;
                $currentSet['primary_currency_symbol']         = $this->primaryCurrency->symbol;
                $currentSet['primary_currency_decimal_places'] = $this->primaryCurrency->decimal_places;
                $pcPrevious                                    = array_values($range)[0]['pc_balance'];

            }

            // also get the primary balance if convertToPrimary is true:
            while ($currentStart <= $end) {
                $format                        = $currentStart->format('Y-m-d');
                $label                         = $currentStart->toAtomString();

                // balance is based on "balance" from the $range variable.
                $balance                       = array_key_exists($format, $range) ? $range[$format]['balance'] : $previous;
                $previous                      = $balance;
                $currentSet['entries'][$label] = $balance;

                // do the same for the primary balance, if relevant:
                $pcBalance                     = null;
                if ($this->convertToPrimary) {
                    $pcBalance                        = array_key_exists($format, $range) ? $range[$format]['pc_balance'] : $pcPrevious;
                    $pcPrevious                       = $pcBalance;
                    $currentSet['pc_entries'][$label] = $pcBalance;
                }

                $currentStart->addDay();

            }
            $chartData[]  = $currentSet;
        }

        return response()->json($chartData);
    }

    private function getFrontPageAccountIds(): array
    {
        $defaultSet = $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value])->pluck('id')->toArray();

        /** @var Preference $frontpage */
        $frontpage  = Preferences::get('frontpageAccounts', $defaultSet);

        if (!(is_array($frontpage->data) && count($frontpage->data) > 0)) {
            $frontpage->data = $defaultSet;
            $frontpage->save();
        }

        return $frontpage->data ?? $defaultSet;
    }
}
