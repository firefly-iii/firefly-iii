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
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Support\Chart\ChartData;
use FireflyIII\Support\Http\Api\CleansChartData;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use CleansChartData;
    use ValidatesUserGroupTrait;

    private AccountRepositoryInterface $repository;
    private ChartData $chartData;
    private TransactionCurrency $default;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));
                $this->chartData = new ChartData();
                $this->default = app('amount')->getDefaultCurrency();

                return $next($request);
            }
        );
    }

    /**
     * TODO fix documentation
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
     * TODO Duplicate function but I think it belongs here or in a separate trait
     *
     */
    private function getAccountList(array $queryParameters): Collection
    {
        $collection = new Collection();

        // always collect from the query parameter, even when it's empty.
        foreach ($queryParameters['accounts'] as $accountId) {
            $account = $this->repository->find((int) $accountId);
            if (null !== $account) {
                $collection->push($account);
            }
        }

        // if no "preselected", and found accounts
        if ('empty' === $queryParameters['preselected'] && $collection->count() > 0) {
            return $collection;
        }
        // if no preselected, but no accounts:
        if ('empty' === $queryParameters['preselected'] && 0 === $collection->count()) {
            $defaultSet = $this->repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT])->pluck('id')->toArray();
            $frontpage  = app('preferences')->get('frontpageAccounts', $defaultSet);

            if (!(is_array($frontpage->data) && count($frontpage->data) > 0)) {
                $frontpage->data = $defaultSet;
                $frontpage->save();
            }

            return $this->repository->getAccountsById($frontpage->data);
        }

        // both options are overruled by "preselected"
        if ('all' === $queryParameters['preselected']) {
            return $this->repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        }
        if ('assets' === $queryParameters['preselected']) {
            return $this->repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        }
        if ('liabilities' === $queryParameters['preselected']) {
            return $this->repository->getAccountsByType([AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        }

        return $collection;
    }

    /**
     * @throws FireflyException
     */
    private function renderAccountData(array $params, Account $account): void {
        $currency = $this->repository->getAccountCurrency($account);
        if (null === $currency) {
            $currency = $this->default;
        }
        $currentSet     = [
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
            'start'                          => $params['start']->toAtomString(),
            'end'                            => $params['end']->toAtomString(),
            'period'                         => '1D',
            'entries'                        => [],
            'native_entries'                 => [],
        ];
        $currentStart   = clone $params['start'];
        $range          = app('steam')->balanceInRange($account, $params['start'], clone $params['end'], $currency);
        $rangeConverted = app('steam')->balanceInRangeConverted($account, $params['start'], clone $params['end'], $this->default);

        $previous          = array_values($range)[0];
        $previousConverted = array_values($rangeConverted)[0];
        while ($currentStart <= $params['end']) {
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
        $this->chartData->add($currentSet);
    }
}
