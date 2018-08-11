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

use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\JsonResponse;

/**
 * Class AutoCompleteController.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoCompleteController extends Controller
{

    /**
     * Returns a JSON list of all accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function allAccounts(AccountRepositoryInterface $repository): JsonResponse
    {
        $return = array_unique(
            $repository->getAccountsByType(
                [AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET]
            )->pluck('name')->toArray()
        );
        sort($return);

        return response()->json($return);
    }

    /**
     * List of all journals.
     *
     * @param TransactionCollectorInterface $collector
     *
     * @return JsonResponse
     */
    public function allTransactionJournals(TransactionCollectorInterface $collector): JsonResponse
    {
        $collector->setLimit(250)->setPage(1);
        $return = array_unique($collector->getTransactions()->pluck('description')->toArray());
        sort($return);

        return response()->json($return);
    }

    /**
     * Returns a JSON list of all bills.
     *
     * @param BillRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function bills(BillRepositoryInterface $repository): JsonResponse
    {
        $return = array_unique(
            $repository->getActiveBills()->pluck('name')->toArray()
        );
        sort($return);

        return response()->json($return);
    }

    /**
     * List of budgets.
     *
     * @param BudgetRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function budgets(BudgetRepositoryInterface $repository): JsonResponse
    {
        $return = array_unique($repository->getBudgets()->pluck('name')->toArray());
        sort($return);

        return response()->json($return);
    }

    /**
     * Returns a list of categories.
     *
     * @param CategoryRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function categories(CategoryRepositoryInterface $repository): JsonResponse
    {
        $return = array_unique($repository->getCategories()->pluck('name')->toArray());
        sort($return);

        return response()->json($return);
    }

    /**
     * List of currency names.
     *
     * @param CurrencyRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function currencyNames(CurrencyRepositoryInterface $repository): JsonResponse
    {
        $return = $repository->get()->pluck('name')->toArray();
        sort($return);

        return response()->json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function expenseAccounts(AccountRepositoryInterface $repository): JsonResponse
    {
        $set      = $repository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $filtered = $set->filter(
            function (Account $account) {
                if (true === $account->active) {
                    return $account;
                }

                return false;
            }
        );
        $return   = array_unique($filtered->pluck('name')->toArray());

        sort($return);

        return response()->json($return);
    }


    /**
     * List of journals with their ID.
     *
     * @param TransactionCollectorInterface $collector
     * @param TransactionJournal        $except
     *
     * @return JsonResponse
     */
    public function journalsWithId(TransactionCollectorInterface $collector, TransactionJournal $except): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('recent-journals-id');

        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $collector->setLimit(400)->setPage(1);
        $set    = $collector->getTransactions()->pluck('description', 'journal_id')->toArray();
        $return = [];
        foreach ($set as $id => $description) {
            $id = (int)$id;
            if ($id !== $except->id) {
                $return[] = [
                    'id'   => $id,
                    'name' => $id . ': ' . $description,
                ];
            }
        }

        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of revenue accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function revenueAccounts(AccountRepositoryInterface $repository): JsonResponse
    {
        $set      = $repository->getAccountsByType([AccountType::REVENUE]);
        $filtered = $set->filter(
            function (Account $account) {
                if (true === $account->active) {
                    return $account;
                }

                return false;
            }
        );
        $return   = array_unique($filtered->pluck('name')->toArray());
        sort($return);

        return response()->json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param TagRepositoryInterface $tagRepository
     *
     * @return JsonResponse
     */
    public function tags(TagRepositoryInterface $tagRepository): JsonResponse
    {
        $return = array_unique($tagRepository->get()->pluck('tag')->toArray());
        sort($return);

        return response()->json($return);
    }

    /**
     * List of journals by type.
     *
     * @param TransactionCollectorInterface $collector
     * @param string                    $what
     *
     * @return JsonResponse
     */
    public function transactionJournals(TransactionCollectorInterface $collector, string $what): JsonResponse
    {
        $type  = config('firefly.transactionTypesByWhat.' . $what);
        $types = [$type];

        $collector->setTypes($types)->setLimit(250)->setPage(1);
        $return = array_unique($collector->getTransactions()->pluck('description')->toArray());
        sort($return);

        return response()->json($return);
    }

    /**
     * List if transaction types.
     *
     * @param JournalRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function transactionTypes(JournalRepositoryInterface $repository): JsonResponse
    {
        $return = array_unique($repository->getTransactionTypes()->pluck('type')->toArray());
        sort($return);

        return response()->json($return);
    }
}
