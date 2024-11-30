<?php

/**
 * PiggyBankController.php
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
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class PiggyBankController
 */
class PiggyBankController extends Controller
{
    private AccountRepositoryInterface   $accountRepository;
    private PiggyBankRepositoryInterface $piggyRepository;

    /**
     * PiggyBankController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                    = auth()->user();
                $this->piggyRepository   = app(PiggyBankRepositoryInterface::class);
                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->piggyRepository->setUser($user);
                $this->accountRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getPiggiesAC
     */
    public function piggyBanks(AutocompleteRequest $request): JsonResponse
    {
        $data            = $request->getData();
        $piggies         = $this->piggyRepository->searchPiggyBank($data['query'], $this->parameters->get('limit'));
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $response        = [];

        /** @var PiggyBank $piggy */
        foreach ($piggies as $piggy) {
            $currency    = $this->accountRepository->getAccountCurrency($piggy->account) ?? $defaultCurrency;
            $objectGroup = $piggy->objectGroups()->first();
            $response[]  = [
                'id'                      => (string)$piggy->id,
                'name'                    => $piggy->name,
                'currency_id'             => (string)$currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'object_group_id'         => null === $objectGroup ? null : (string)$objectGroup->id,
                'object_group_title'      => $objectGroup?->title,
            ];
        }

        return response()->json($response);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getPiggiesBalanceAC
     */
    public function piggyBanksWithBalance(AutocompleteRequest $request): JsonResponse
    {
        $data            = $request->getData();
        $piggies         = $this->piggyRepository->searchPiggyBank($data['query'], $this->parameters->get('limit'));
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $response        = [];

        /** @var PiggyBank $piggy */
        foreach ($piggies as $piggy) {
            $currency      = $this->accountRepository->getAccountCurrency($piggy->account) ?? $defaultCurrency;
            $currentAmount = $this->piggyRepository->getRepetition($piggy)->current_amount ?? '0';
            $objectGroup   = $piggy->objectGroups()->first();
            $response[]    = [
                'id'                      => (string)$piggy->id,
                'name'                    => $piggy->name,
                'name_with_balance'       => sprintf(
                    '%s (%s / %s)',
                    $piggy->name,
                    app('amount')->formatAnything($currency, $currentAmount, false),
                    app('amount')->formatAnything($currency, $piggy->target_amount, false),
                ),
                'currency_id'             => (string)$currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'object_group_id'         => null === $objectGroup ? null : (string)$objectGroup->id,
                'object_group_title'      => $objectGroup?->title,
            ];
        }

        return response()->json($response);
    }
}
