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
use Illuminate\Http\Request;

/**
 * TODO refactor so each auto-complete thing is a function call because lots of code duplication.
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
     * @param Request                    $request
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function allAccounts(Request $request, AccountRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-all-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = array_values(
            array_unique(
                $repository->getAccountsByType(
                    [AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET]
                )->pluck('name')->toArray()
            )
        );
        if ('' !== $search) {
            $return = array_values(
                array_filter(
                    $return, function (string $value) use ($search) {
                    return !(false === stripos($value, $search));
                }, ARRAY_FILTER_USE_BOTH
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of all journals.
     *
     * @param Request                       $request
     * @param TransactionCollectorInterface $collector
     *
     * @return JsonResponse
     */
    public function allTransactionJournals(Request $request, TransactionCollectorInterface $collector): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-all-journals');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $collector->setLimit(250)->setPage(1);
        $return = array_values(array_unique($collector->getTransactions()->pluck('description')->toArray()));

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of revenue accounts.
     *
     * @param Request                    $request
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function assetAccounts(Request $request, AccountRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-asset-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $set      = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $filtered = $set->filter(
            function (Account $account) {
                if (true === $account->active) {
                    return $account;
                }

                return false; // @codeCoverageIgnore
            }
        );
        $return   = array_values(array_unique($filtered->pluck('name')->toArray()));

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * Returns a JSON list of all bills.
     *
     * @param Request                 $request
     * @param BillRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function bills(Request $request, BillRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-bills');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = array_unique($repository->getActiveBills()->pluck('name')->toArray());

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of budgets.
     *
     * @param Request                   $request
     * @param BudgetRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function budgets(Request $request, BudgetRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-budgets');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = array_unique($repository->getBudgets()->pluck('name')->toArray());

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * Returns a list of categories.
     *
     * @param Request                     $request
     * @param CategoryRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function categories(Request $request, CategoryRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-categories');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = array_unique($repository->getCategories()->pluck('name')->toArray());
        if ('' !== $search) {
            $return = array_values(
                array_filter(
                    $return, function (string $value) use ($search) {
                    return !(false === stripos($value, $search));
                }, ARRAY_FILTER_USE_BOTH
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of currency names.
     *
     * @param Request                     $request
     * @param CurrencyRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function currencyNames(Request $request, CurrencyRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-currency-names');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = $repository->get()->pluck('name')->toArray();
        sort($return);

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param Request                    $request
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function expenseAccounts(Request $request, AccountRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-expense-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
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

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of journals with their ID.
     *
     * @param Request                       $request
     * @param TransactionCollectorInterface $collector
     * @param TransactionJournal            $except
     *
     * @return JsonResponse
     */
    public function journalsWithId(Request $request, TransactionCollectorInterface $collector, TransactionJournal $except): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-expense-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
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

        sort($return);

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (array $array) use ($search) {
                        $value = $array['name'];

                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of revenue accounts.
     *
     * @param Request                    $request
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function revenueAccounts(Request $request, AccountRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-revenue-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
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

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param Request                $request
     * @param TagRepositoryInterface $tagRepository
     *
     * @return JsonResponse
     */
    public function tags(Request $request, TagRepositoryInterface $tagRepository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-revenue-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = array_unique($tagRepository->get()->pluck('tag')->toArray());
        sort($return);

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List of journals by type.
     *
     * @param Request                       $request
     * @param TransactionCollectorInterface $collector
     * @param string                        $what
     *
     * @return JsonResponse
     */
    public function transactionJournals(Request $request, TransactionCollectorInterface $collector, string $what): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-revenue-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $type  = config('firefly.transactionTypesByWhat.' . $what);
        $types = [$type];

        $collector->setTypes($types)->setLimit(250)->setPage(1);
        $return = array_unique($collector->getTransactions()->pluck('description')->toArray());
        sort($return);

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);
    }

    /**
     * List if transaction types.
     *
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function transactionTypes(Request $request, JournalRepositoryInterface $repository): JsonResponse
    {
        $search = (string)$request->get('search');
        $cache  = new CacheProperties;
        $cache->addProperty('ac-revenue-accounts');
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        // find everything:
        $return = array_unique($repository->getTransactionTypes()->pluck('type')->toArray());
        sort($return);

        if ('' !== $search) {
            $return = array_values(
                array_unique(
                    array_filter(
                        $return, function (string $value) use ($search) {
                        return !(false === stripos($value, $search));
                    }, ARRAY_FILTER_USE_BOTH
                    )
                )
            );
        }
        $cache->store($return);

        return response()->json($return);


    }
}
