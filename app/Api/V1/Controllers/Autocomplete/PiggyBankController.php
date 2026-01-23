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
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteApiRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class PiggyBankController
 */
class PiggyBankController extends Controller
{
    private AccountRepositoryInterface $accountRepository;
    private PiggyBankRepositoryInterface $piggyRepository;
    protected array $acceptedRoles = [UserRoleEnum::READ_PIGGY_BANKS];

    /**
     * PiggyBankController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function (Request $request, $next) {
            $this->validateUserGroup($request);
            $this->piggyRepository   = app(PiggyBankRepositoryInterface::class);
            $this->accountRepository = app(AccountRepositoryInterface::class);
            $this->piggyRepository->setUser($this->user);
            $this->piggyRepository->setUserGroup($this->userGroup);
            $this->accountRepository->setUser($this->user);
            $this->accountRepository->setUserGroup($this->userGroup);

            return $next($request);
        });
    }

    public function piggyBanks(AutocompleteApiRequest $request): JsonResponse
    {
        $piggies  = $this->piggyRepository->searchPiggyBank($request->attributes->get('query'), $request->attributes->get('limit'));
        $response = [];

        /** @var PiggyBank $piggy */
        foreach ($piggies as $piggy) {
            $currency    = $piggy->transactionCurrency;
            $objectGroup = $piggy->objectGroups()->first();
            $response[]  = [
                'id'                      => (string) $piggy->id,
                'name'                    => $piggy->name,
                'currency_id'             => (string) $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'object_group_id'         => null === $objectGroup ? null : (string) $objectGroup->id,
                'object_group_title'      => $objectGroup?->title,
                'object_group_order'      => $objectGroup?->order,
            ];
        }

        return response()->api($response);
    }

    public function piggyBanksWithBalance(AutocompleteApiRequest $request): JsonResponse
    {
        $piggies  = $this->piggyRepository->searchPiggyBank($request->attributes->get('query'), $request->attributes->get('limit'));
        $response = [];

        /** @var PiggyBank $piggy */
        foreach ($piggies as $piggy) {
            /** @var TransactionCurrency $currency */
            $currency      = $piggy->transactionCurrency;
            $currentAmount = $this->piggyRepository->getCurrentAmount($piggy);
            $objectGroup   = $piggy->objectGroups()->first();
            $response[]    = [
                'id'                      => (string) $piggy->id,
                'name'                    => $piggy->name,
                'name_with_balance'       => sprintf(
                    '%s (%s / %s)',
                    $piggy->name,
                    Amount::formatAnything($currency, $currentAmount, false),
                    Amount::formatAnything($currency, $piggy->target_amount, false)
                ),
                'currency_id'             => (string) $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'object_group_id'         => null === $objectGroup ? null : (string) $objectGroup->id,
                'object_group_title'      => $objectGroup?->title,
            ];
        }

        return response()->api($response);
    }
}
