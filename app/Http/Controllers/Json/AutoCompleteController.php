<?php
/**
 * AutoCompleteController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Response;

/**
 * Class AutoCompleteController
 *
 * @package FireflyIII\Http\Controllers\Json
 */
class AutoCompleteController extends Controller
{

    /**
     * Returns a JSON list of all accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function allAccounts(AccountRepositoryInterface $repository)
    {
        $return = array_unique(
            $repository->getAccountsByType(
                [AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET]
            )->pluck('name')->toArray()
        );
        sort($return);

        return Response::json($return);

    }

    /**
     * @param JournalCollectorInterface $collector
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allTransactionJournals(JournalCollectorInterface $collector)
    {
        $collector->setLimit(250)->setPage(1);
        $return = array_unique($collector->getJournals()->pluck('description')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function expenseAccounts(AccountRepositoryInterface $repository)
    {
        $set      = $repository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $filtered = $set->filter(
            function (Account $account) {
                if ($account->active) {
                    return $account;
                }

                return false;
            }
        );
        $return   = array_unique($filtered->pluck('name')->toArray());

        sort($return);

        return Response::json($return);
    }

    /**
     * @param JournalCollectorInterface $collector
     *
     * @param TransactionJournal        $except
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function journalsWithId(JournalCollectorInterface $collector, TransactionJournal $except)
    {

        $cache = new CacheProperties;
        $cache->addProperty('recent-journals-id');

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $collector->setLimit(400)->setPage(1);
        $set    = $collector->getJournals()->pluck('description', 'journal_id')->toArray();
        $return = [];
        foreach ($set as $id => $description) {
            $id = intval($id);
            if ($id !== $except->id) {
                $return[] = [
                    'id'   => $id,
                    'name' => $id . ': ' . $description,
                ];
            }
        }

        $cache->store($return);

        return Response::json($return);
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function revenueAccounts(AccountRepositoryInterface $repository)
    {
        $set      = $repository->getAccountsByType([AccountType::REVENUE]);
        $filtered = $set->filter(
            function (Account $account) {
                if ($account->active) {
                    return $account;
                }

                return false;
            }
        );
        $return   = array_unique($filtered->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * @param JournalCollectorInterface $collector
     * @param string                    $what
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionJournals(JournalCollectorInterface $collector, string $what)
    {
        $type  = config('firefly.transactionTypesByWhat.' . $what);
        $types = [$type];

        $collector->setTypes($types)->setLimit(250)->setPage(1);
        $return = array_unique($collector->getJournals()->pluck('description')->toArray());
        sort($return);

        return Response::json($return);
    }

}
