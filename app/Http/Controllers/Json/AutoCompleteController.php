<?php
/**
 * AutoCompleteController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class AutoCompleteController.
 *
 * TODO autocomplete for transaction types.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoCompleteController extends Controller
{

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function accounts(Request $request): JsonResponse
    {
        $accountTypes = explode(',', $request->get('types') ?? '');
        $search       = $request->get('query');
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        // filter the account types:
        $allowedAccountTypes  = [AccountType::ASSET, AccountType::EXPENSE, AccountType::REVENUE, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE,];
        $filteredAccountTypes = [];
        foreach ($accountTypes as $type) {
            if (in_array($type, $allowedAccountTypes, true)) {
                $filteredAccountTypes[] = $type;
            }
        }
        Log::debug('Now in accounts(). Filtering results.', $filteredAccountTypes);

        $return          = [];
        $result          = $repository->searchAccount((string)$search, $filteredAccountTypes);
        $defaultCurrency = app('amount')->getDefaultCurrency();

        /** @var Account $account */
        foreach ($result as $account) {
            $currency = $repository->getAccountCurrency($account);
            $currency = $currency ?? $defaultCurrency;
            $return[] = [
                'id'                      => $account->id,
                'name'                    => $account->name,
                'type'                    => $account->accountType->type,
                'currency_id'             => $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_decimal_places' => $currency->decimal_places,
            ];
        }


        return response()->json($return);
    }

    /**
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function budgets(): JsonResponse
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);

        return response()->json($repository->getActiveBudgets()->toArray());
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function categories(Request $request): JsonResponse
    {
        $query = (string)$request->get('query');
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $result     = $repository->searchCategory($query);

        return response()->json($result->toArray());
    }

    /**
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function currencies(): JsonResponse
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $return     = [];
        $collection = $repository->getAll();

        /** @var TransactionCurrency $currency */
        foreach ($collection as $currency) {
            $return[] = [
                'id'             => $currency->id,
                'name'           => $currency->name,
                'code'           => $currency->code,
                'symbol'         => $currency->symbol,
                'enabled'        => $currency->enabled,
                'decimal_places' => $currency->decimal_places,
            ];
        }

        return response()->json($return);
    }

    /**
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function piggyBanks(): JsonResponse
    {
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);

        return response()->json($repository->getPiggyBanks()->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function tags(Request $request): JsonResponse
    {
        $query = (string)$request->get('query');
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $result     = $repository->searchTags($query);


        return response()->json($result->toArray());
    }

}
