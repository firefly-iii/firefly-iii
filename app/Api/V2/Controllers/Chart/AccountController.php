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

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Generic\DateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Administration\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\CleansChartData;
use Illuminate\Http\JsonResponse;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use CleansChartData;

    private AccountRepositoryInterface $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setAdministrationId(auth()->user()->user_group_id);
                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v2)#/charts/getChartAccountOverview
     *
     * The native currency is the preferred currency on the page /currencies.
     *
     * If a transaction has foreign currency = native currency, the foreign amount will be used, no conversion
     * will take place.
     *
     * TODO validate and set administration_id from request
     *
     * @param DateRequest $request
     *
     * @return JsonResponse
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws FireflyException
     */
    public function dashboard(DateRequest $request): JsonResponse
    {
        /** @var Carbon $start */
        $start = $this->parameters->get('start');
        /** @var Carbon $end */
        $end = $this->parameters->get('end');
        $end->endOfDay();

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT])->pluck('id')->toArray();
        $frontPage  = app('preferences')->get('frontPageAccounts', $defaultSet);
        /** @var TransactionCurrency $default */
        $default   = app('amount')->getDefaultCurrency();
        $accounts  = $this->repository->getAccountsById($frontPage->data);
        $chartData = [];

        if (!(is_array($frontPage->data) && count($frontPage->data) > 0)) {
            $frontPage->data = $defaultSet;
            $frontPage->save();
        }
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet     = [
                'label'                   => $account->name,
                // the currency that belongs to the account.
                'currency_id'             => (string)$currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,

                // the default currency of the user (could be the same!)
                'native_id'               => (int)$default->id,
                'native_code'             => $default->code,
                'native_symbol'           => $default->symbol,
                'native_decimal_places'   => (int)$default->decimal_places,
                'start'                   => $start->toAtomString(),
                'end'                     => $end->toAtomString(),
                'period'                  => '1D',
                'entries'                 => [],
                'native_entries'          => [],
            ];
            $currentStart   = clone $start;
            $range          = app('steam')->balanceInRange($account, $start, clone $end, $currency);
            $rangeConverted = app('steam')->balanceInRangeConverted($account, $start, clone $end, $default);

            $previous          = array_values($range)[0];
            $previousConverted = array_values($rangeConverted)[0];
            while ($currentStart <= $end) {
                $format            = $currentStart->format('Y-m-d');
                $label             = $currentStart->toAtomString();
                $balance           = array_key_exists($format, $range) ? $range[$format] : $previous;
                $balanceConverted  = array_key_exists($format, $rangeConverted) ? $rangeConverted[$format] : $previousConverted;
                $previous          = $balance;
                $previousConverted = $balanceConverted;

                $currentStart->addDay();
                $currentSet['entries'][$label]        = $balance;
                $currentSet['native_entries'][$label] = $balanceConverted;
            }
            $chartData[] = $currentSet;
        }

        return response()->json($this->clean($chartData));
    }

}
