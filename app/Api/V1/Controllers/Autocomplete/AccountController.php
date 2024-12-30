<?php

/**
 * AccountController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Autocomplete;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteRequest;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use AccountFilter;

    /** @var array<int, string> */
    private array                      $balanceTypes;
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
        $this->balanceTypes = [AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
    }

    /**
     * Documentation for this endpoint:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getAccountsAC
     *
     * @throws FireflyException
     * @throws FireflyException
     */
    public function accounts(AutocompleteRequest $request): JsonResponse
    {
        $data   = $request->getData();
        $types  = $data['types'];
        $query  = $data['query'];
        $date   = $data['date'] ?? today(config('app.timezone'));
        $return = [];
        $result = $this->repository->searchAccount((string) $query, $types, $this->parameters->get('limit'));

        /** @var Account $account */
        foreach ($result as $account) {
            $nameWithBalance = $account->name;
            $currency        = $this->repository->getAccountCurrency($account) ?? $this->defaultCurrency;
            $useCurrency     = $currency;
            if (in_array($account->accountType->type, $this->balanceTypes, true)) {
                $balance         = Steam::finalAccountBalance($account, $date);
                $key             = $this->convertToNative && $currency->id !== $this->defaultCurrency->id ? 'native_balance' : 'balance';
                $useCurrency     = $this->convertToNative && $currency->id !== $this->defaultCurrency->id ? $this->defaultCurrency : $currency;
                $amount          = $balance[$key] ?? '0';
                $nameWithBalance = sprintf(
                    '%s (%s)',
                    $account->name,
                    app('amount')->formatAnything($useCurrency, $amount, false)
                );
            }

            $return[] = [
                'id'                      => (string) $account->id,
                'name'                    => $account->name,
                'name_with_balance'       => $nameWithBalance,
                'type'                    => $account->accountType->type,
                'currency_id'             => (string) $useCurrency->id,
                'currency_name'           => $useCurrency->name,
                'currency_code'           => $useCurrency->code,
                'currency_symbol'         => $useCurrency->symbol,
                'currency_decimal_places' => $useCurrency->decimal_places,
            ];
        }

        // custom order.
        usort(
            $return,
            static function (array $left, array $right) {
                $order = [AccountTypeEnum::ASSET->value, AccountTypeEnum::REVENUE->value, AccountTypeEnum::EXPENSE->value];
                $posA  = (int) array_search($left['type'], $order, true);
                $posB  = (int) array_search($right['type'], $order, true);

                return $posA - $posB;
            }
        );

        return response()->json($return);
    }
}
