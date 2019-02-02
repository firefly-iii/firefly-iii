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
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AutoCompleteCollector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class AutoCompleteController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoCompleteController extends Controller
{
    use AutoCompleteCollector;

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

        switch ($subject) {
            default:
                break;
            case 'all-accounts':
                $unfiltered = $this->getAccounts(
                    [AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN,
                     AccountType::DEBT, AccountType::MORTGAGE]
                );
                break;
            case 'expense-accounts':
                $unfiltered = $this->getAccounts([AccountType::EXPENSE, AccountType::BENEFICIARY]);
                break;
            case 'revenue-accounts':
                $unfiltered = $this->getAccounts([AccountType::REVENUE]);
                break;
            case 'asset-accounts':
                $unfiltered = $this->getAccounts([AccountType::ASSET, AccountType::DEFAULT]);
                break;
            case 'categories':
                $unfiltered = $this->getCategories();
                break;
            case 'budgets':
                $unfiltered = $this->getBudgets();
                break;
            case 'tags':
                $unfiltered = $this->getTags();
                break;
            case 'bills':
                $unfiltered = $this->getBills();
                break;
            case 'currency-names':
                $unfiltered = $this->getCurrencyNames();
                break;
            case 'transaction-types':
            case 'transaction_types':
                $unfiltered = $this->getTransactionTypes();
                break;
        }
        $filtered = $this->filterResult($unfiltered, $search);

        if (null === $filtered) {
            throw new FireflyException(sprintf('Auto complete handler cannot handle "%s"', $subject)); // @codeCoverageIgnore
        }

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
                $result   = stripos($haystack, $search);

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
        $cache->addProperty('ac-journals');
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
}
