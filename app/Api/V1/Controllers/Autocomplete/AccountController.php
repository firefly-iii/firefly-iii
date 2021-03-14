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
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use AccountFilter;

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
        $this->balanceTypes = [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE,];
    }

    /**
     * @param AutocompleteRequest $request
     *
     * @return JsonResponse
     */
    public function accounts(AutocompleteRequest $request): JsonResponse
    {
        $data  = $request->getData();
        $types = $data['types'];
        $query = $data['query'];
        $date  = $data['date'] ?? today(config('app.timezone'));

        $return          = [];
        $result          = $this->repository->searchAccount((string)$query, $types, $data['limit']);
        $defaultCurrency = app('amount')->getDefaultCurrency();

        /** @var Account $account */
        foreach ($result as $account) {
            $nameWithBalance = $account->name;
            $currency        = $this->repository->getAccountCurrency($account) ?? $defaultCurrency;

            if (in_array($account->accountType->type, $this->balanceTypes, true)) {
                $balance         = app('steam')->balance($account, $date);
                $nameWithBalance = sprintf('%s (%s)', $account->name, app('amount')->formatAnything($currency, $balance, false));
            }

            $return[] = [
                'id'                      => (string)$account->id,
                'name'                    => $account->name,
                'name_with_balance'       => $nameWithBalance,
                'type'                    => $account->accountType->type,
                'currency_id'             => $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
            ];
        }

        // custom order.
        $order = [AccountType::ASSET, AccountType::REVENUE, AccountType::EXPENSE];


        usort(
            $return, function ($a, $b) use ($order) {
            $pos_a = array_search($a['type'], $order);
            $pos_b = array_search($b['type'], $order);

            return $pos_a - $pos_b;
        }
        );

        return response()->json($return);
    }
}
