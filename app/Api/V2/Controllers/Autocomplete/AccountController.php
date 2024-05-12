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

namespace FireflyIII\Api\V2\Controllers\Autocomplete;

use Carbon\Carbon;
use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Autocomplete\AutocompleteRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface as AdminAccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{

    //    use AccountFilter;
    private AdminAccountRepositoryInterface $adminRepository;
    private TransactionCurrency             $default;
    private ExchangeRateConverter           $converter;

//    private array                           $balanceTypes;
//    private AccountRepositoryInterface      $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                // new way of user group validation
                $userGroup             = $this->validateUserGroup($request);
                $this->adminRepository = app(AdminAccountRepositoryInterface::class);
                $this->adminRepository->setUserGroup($userGroup);
                $this->default   = app('amount')->getDefaultCurrency();
                $this->converter = app(ExchangeRateConverter::class);

//                $this->repository      = app(AccountRepositoryInterface::class);
                //                $this->adminRepository->setUserGroup($this->validateUserGroup($request));

                return $next($request);
            }
        );
//        $this->balanceTypes = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
    }

    /**
     * Documentation for this endpoint:
     * TODO list of checks
     * 1. use dates from ParameterBag
     * 2. Request validates dates
     * 3. Request includes user_group_id
     * 4. Endpoint is documented.
     * 5. Collector uses user_group_id
     *
     * @throws FireflyException
     */
    public function accounts(AutocompleteRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $result          = $this->adminRepository->searchAccount((string) $queryParameters['query'], $queryParameters['account_types'], $queryParameters['size']);
        $return          = [];

        /** @var Account $account */
        foreach ($result as $account) {
            $return[] = $this->parseAccount($account);
        }

        return response()->json($return);
    }

    private function parseAccount(Account $account): array
    {
        $currency = $this->adminRepository->getAccountCurrency($account);
        return [
            'id'    => (string) $account->id,
            'title' => $account->name,
            'meta'  => [
                'type'             => $account->accountType->type,
                'currency_id'      => null === $currency ? null : (string) $currency->id,
                'currency_code'    => $currency?->code,
                'currency_symbol'  => $currency?->symbol,
                'currency_decimal' => $currency?->decimal_places,
                'account_balances' => $this->getAccountBalances($account),
            ],
        ];
    }

    private function getAccountBalances(Account $account): array
    {
        $return   = [];
        $balances = $this->adminRepository->getAccountBalances($account);
        /** @var AccountBalance $balance */
        foreach ($balances as $balance) {
            $return[] = $this->parseAccountBalance($balance);
        }
        return $return;
    }

    /**
     * @param AccountBalance $balance
     *
     * @return array
     */
    private function parseAccountBalance(AccountBalance $balance): array
    {
        $currency = $balance->transactionCurrency;
        return [
            'title'                   => $balance->title,
            'native_amount'           => $this->converter->convert($currency, $this->default, today(), $balance->balance),
            'amount'                  => app('steam')->bcround($balance->balance, $currency->decimal_places),
            'currency_id'             => (string) $currency->id,
            'currency_code'           => $currency->code,
            'currency_symbol'         => $currency->symbol,
            'currency_decimal_places' => $currency->decimal_places,
            'native_currency_id'      => (string) $this->default->id,
            'native_currency_code'    => $this->default->code,
            'native_currency_symbol'  => $this->default->symbol,
            'native_currency_decimal' => $this->default->decimal_places,

        ];

    }
}
