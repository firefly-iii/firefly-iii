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

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Chart\ChartRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\ApiSupport;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\CollectsAccountsFromFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use ApiSupport;
    use CleansChartData;
    use CollectsAccountsFromFilter;

    protected array $acceptedRoles            = [UserRoleEnum::READ_ONLY];

    private array                  $chartData = [];
    private AccountRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->validateUserGroup($request);
                $this->repository->setUserGroup($this->userGroup);
                $this->repository->setUser($this->user);

                return $next($request);
            }
        );
    }

    /**
     * @throws FireflyException
     */
    public function overview(ChartRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $accounts        = $this->getAccountList($queryParameters);

        // move date to end of day
        $queryParameters['start']->startOfDay();
        $queryParameters['end']->endOfDay();
        // Log::debug(sprintf('dashboard(), convert to primary: %s', var_export($this->convertToPrimary, true)));

        // loop each account, and collect info:
        /** @var Account $account */
        foreach ($accounts as $account) {
            Log::debug(sprintf('Account #%d ("%s")', $account->id, $account->name));
            $this->renderAccountData($queryParameters, $account);
        }

        return response()->json($this->clean($this->chartData));
    }

    /**
     * @throws FireflyException
     */
    private function renderAccountData(array $params, Account $account): void
    {
        Log::debug(sprintf('Now in %s(array, #%d)', __METHOD__, $account->id));
        $currency          = $this->repository->getAccountCurrency($account);
        $currentStart      = clone $params['start'];
        $range             = Steam::finalAccountBalanceInRange($account, $params['start'], clone $params['end'], $this->convertToPrimary);


        $previous          = array_values($range)[0]['balance'];
        $pcPrevious        = null;
        if (!$currency instanceof TransactionCurrency) {
            $currency = $this->primaryCurrency;
        }
        $currentSet        = [
            'label'                   => $account->name,

            // the currency that belongs to the account.
            'currency_id'             => (string)$currency->id,
            'currency_name'           => $currency->name,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,

            // the primary currency
            'primary_currency_id'     => (string)$this->primaryCurrency->id,

            // the default currency of the user (could be the same!)
            'date'                    => $params['start']->toAtomString(),
            'start_date'              => $params['start']->toAtomString(),
            'end_date'                => $params['end']->toAtomString(),
            'type'                    => 'line',
            'yAxisID'                 => 0,
            'period'                  => '1D',
            'entries'                 => [],
            'pc_entries'              => [],
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
        $this->chartData[] = $currentSet;
    }
}
