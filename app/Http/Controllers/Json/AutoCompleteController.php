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

use FireflyIII\Exceptions\FireflyException;
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
use Illuminate\Support\Collection;

/**
 * Class AutoCompleteController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoCompleteController extends Controller
{

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
            return response()->json($cache->get()); // @codeCoverageIgnore
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
     * @param Request $request
     * @param string  $subject
     *
     * @throws FireflyException
     * @return JsonResponse
     */
    public function autoComplete(Request $request, string $subject): JsonResponse
    {
        $search     = (string)$request->get('search');
        $unfiltered = null;
        $filtered   = null;
        $cache      = new CacheProperties;
        $cache->addProperty($subject);
        // very unlikely a user will actually search for this string.
        $key = '' === $search ? 'skjf0893j89fj2398hd89dh289h2398hr7isd8900828u209ujnxs88929282u' : $search;
        $cache->addProperty($key);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        // search for all accounts.
        if ('all-accounts' === $subject) {
            $unfiltered = $this->getAccounts(
                [AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN,
                 AccountType::DEBT, AccountType::MORTGAGE]
            );
        }

        // search for expense accounts.
        if ('expense-accounts' === $subject) {
            $unfiltered = $this->getAccounts([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        }

        // search for revenue accounts.
        if ('revenue-accounts' === $subject) {
            $unfiltered = $this->getAccounts([AccountType::REVENUE]);
        }

        // search for asset accounts.
        if ('asset-accounts' === $subject) {
            $unfiltered = $this->getAccounts([AccountType::ASSET, AccountType::DEFAULT]);
        }

        // search for categories.
        if ('categories' === $subject) {
            $unfiltered = $this->getCategories();
        }

        // search for budgets.
        if ('budgets' === $subject) {
            $unfiltered = $this->getBudgets();
        }

        // search for tags
        if ('tags' === $subject) {
            $unfiltered = $this->getTags();
        }

        // search for bills
        if ('bills' === $subject) {
            $unfiltered = $this->getBills();
        }
        // search for currency names.
        if ('currency-names' === $subject) {
            $unfiltered = $this->getCurrencyNames();
        }
        if ('transaction_types' === $subject) {
            $unfiltered = $this->getTransactionTypes();
        }


        // filter results
        $filtered = $this->filterResult($unfiltered, $search);

        if (null === $filtered) {
            throw new FireflyException(sprintf('Auto complete handler cannot handle "%s"', $subject)); // @codeCoverageIgnore
        }
        $cache->store($filtered);

        return response()->json($filtered);
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
            return response()->json($cache->get()); // @codeCoverageIgnore
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
            $return = array_filter(
                $return, function (array $array) use ($search) {
                $haystack = $array['name'];
                $result = stripos($haystack, $search);
                return !(false === $result);
            }
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
            return response()->json($cache->get()); // @codeCoverageIgnore
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
     * @param array  $unfiltered
     * @param string $query
     *
     * @return array|null
     */
    private function filterResult(?array $unfiltered, string $query): ?array
    {
        if (null === $unfiltered) {
            return null; // @codeCoverageIgnore
        }
        if ('' === $query) {
            sort($unfiltered);

            return $unfiltered;
        }
        $return = [];
        if ('' !== $query) {
            $return = array_values(
                array_filter(
                    $unfiltered, function (string $value) use ($query) {
                    return !(false === stripos($value, $query));
                }, ARRAY_FILTER_USE_BOTH
                )
            );
        }
        sort($return);


        return $return;
    }

    /**
     * @param string $query
     * @param array  $types
     *
     * @return array
     */
    private function getAccounts(array $types): array
    {
        $repository = app(AccountRepositoryInterface::class);
        // find everything:
        /** @var Collection $collection */
        $collection = $repository->getAccountsByType($types);
        $filtered   = $collection->filter(
            function (Account $account) {
                return $account->active === true;
            }
        );
        $return     = array_values(array_unique($filtered->pluck('name')->toArray()));

        return $return;

    }

    /**
     * @return array
     */
    private function getBills(): array
    {
        $repository = app(BillRepositoryInterface::class);

        return array_unique($repository->getActiveBills()->pluck('name')->toArray());
    }

    /**
     * @return array
     */
    private function getBudgets(): array
    {
        $repository = app(BudgetRepositoryInterface::class);

        return array_unique($repository->getBudgets()->pluck('name')->toArray());
    }

    /**
     * @return array
     */
    private function getCategories(): array
    {
        $repository = app(CategoryRepositoryInterface::class);

        return array_unique($repository->getCategories()->pluck('name')->toArray());
    }

    /**
     * @return array
     */
    private function getCurrencyNames(): array
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);

        return $repository->get()->pluck('name')->toArray();
    }

    /**
     * @return array
     */
    private function getTags(): array
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);

        return array_unique($repository->get()->pluck('tag')->toArray());
    }

    /**
     * @return array
     */
    private function getTransactionTypes(): array
    {
        $repository = app(JournalRepositoryInterface::class);

        return array_unique($repository->getTransactionTypes()->pluck('type')->toArray());
    }
}
