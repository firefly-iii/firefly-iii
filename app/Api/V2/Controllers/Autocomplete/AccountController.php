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

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Autocomplete\AutocompleteRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountBalance;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\UserGroups\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\ExchangeRateConverter;
use FireflyIII\Support\Http\Api\ValidatesUserGroupTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    private ExchangeRateConverter      $converter;
    private TransactionCurrency        $default;
    private AccountRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $userGroup        = $this->validateUserGroup($request);
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUserGroup($userGroup);
                $this->default    = app('amount')->getNativeCurrency();
                $this->converter  = app(ExchangeRateConverter::class);

                return $next($request);
            }
        );
    }

    /**
     * Documentation: https://api-docs.firefly-iii.org/?urls.primaryName=2.1.0%20(v2)#/autocomplete/getAccountsAC
     */
    public function accounts(AutocompleteRequest $request): JsonResponse
    {
        $params = $request->getParameters();
        $result = $this->repository->searchAccount($params['query'], $params['account_types'], $params['page'], $params['size']);
        $return = [];

        /** @var Account $account */
        foreach ($result as $account) {
            $return[] = $this->parseAccount($account);
        }

        return response()->json($return);
    }

    private function parseAccount(Account $account): array
    {
        $currency = $this->repository->getAccountCurrency($account);

        return [
            'id'    => (string) $account->id,
            'title' => $account->name,
            'meta'  => [
                'type'                    => $account->accountType->type,
                // TODO is multi currency property.
                'currency_id'             => null === $currency ? null : (string) $currency->id,
                'currency_code'           => $currency?->code,
                'currency_symbol'         => $currency?->symbol,
                'currency_decimal_places' => $currency?->decimal_places,
                'account_balances'        => $this->getAccountBalances($account),
            ],
        ];
    }

    private function getAccountBalances(Account $account): array
    {
        $return   = [];
        $balances = $this->repository->getAccountBalances($account);

        /** @var AccountBalance $balance */
        foreach ($balances as $balance) {
            try {
                $return[] = $this->parseAccountBalance($balance);
            } catch (FireflyException $e) {
                Log::error(sprintf('Could not parse convert account balance: %s', $e->getMessage()));
            }
        }

        return $return;
    }

    /**
     * @throws FireflyException
     */
    private function parseAccountBalance(AccountBalance $balance): array
    {
        $currency = $balance->transactionCurrency;

        return [
            'title'                          => $balance->title,
            'native_amount'                  => $this->converter->convert($currency, $this->default, today(), $balance->balance),
            'amount'                         => app('steam')->bcround($balance->balance, $currency->decimal_places),
            'currency_id'                    => (string) $currency->id,
            'currency_code'                  => $currency->code,
            'currency_symbol'                => $currency->symbol,
            'currency_decimal_places'        => $currency->decimal_places,
            'native_currency_id'             => (string) $this->default->id,
            'native_currency_code'           => $this->default->code,
            'native_currency_symbol'         => $this->default->symbol,
            'native_currency_decimal_places' => $this->default->decimal_places,
        ];
    }
}
