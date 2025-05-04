<?php

/*
 * ListController.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Models\TransactionCurrency;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\Http\Api\AccountFilter;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Support\JsonApi\Enrichments\AccountEnrichment;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use FireflyIII\Transformers\BillTransformer;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\RecurrenceTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class ListController
 */
class ListController extends Controller
{
    use AccountFilter;
    use TransactionFilter;

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listAccountByCurrency
     * Display a list of accounts.
     *
     * @throws FireflyException
     */
    public function accounts(Request $request, TransactionCurrency $currency): JsonResponse
    {
        $manager           = $this->getManager();

        // read type from URL
        $type              = $request->get('type') ?? 'all';
        $this->parameters->set('type', $type);

        // types to get, page size:
        $types             = $this->mapAccountTypes($this->parameters->get('type'));
        $pageSize          = $this->parameters->get('limit');

        // get list of accounts. Count it and split it.
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $unfiltered        = $accountRepository->getAccountsByType($types);

        // filter list on currency preference:
        $collection        = $unfiltered->filter(
            static function (Account $account) use ($currency, $accountRepository) {
                $currencyId = (int) $accountRepository->getMetaValue($account, 'currency_id');

                return $currencyId === $currency->id;
            }
        );

        $count             = $collection->count();
        $accounts          = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // enrich
        /** @var User $admin */
        $admin             = auth()->user();
        $enrichment        = new AccountEnrichment();
        $enrichment->setUser($admin);
        $enrichment->setNative($this->nativeCurrency);
        $accounts          = $enrichment->enrich($accounts);

        // make paginator:
        $paginator         = new LengthAwarePaginator($accounts, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.accounts', [$currency->code]).$this->buildParams());

        /** @var AccountTransformer $transformer */
        $transformer       = app(AccountTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource          = new FractalCollection($accounts, $transformer, 'accounts');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listAvailableBudgetByCurrency
     *
     * Display a listing of the resource.
     *
     * @throws FireflyException
     */
    public function availableBudgets(TransactionCurrency $currency): JsonResponse
    {
        $manager          = $this->getManager();
        // types to get, page size:
        $pageSize         = $this->parameters->get('limit');

        // get list of available budgets. Count it and split it.
        /** @var AvailableBudgetRepositoryInterface $abRepository */
        $abRepository     = app(AvailableBudgetRepositoryInterface::class);

        $collection       = $abRepository->getAvailableBudgetsByCurrency($currency);
        $count            = $collection->count();
        $availableBudgets = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        // make paginator:
        $paginator        = new LengthAwarePaginator($availableBudgets, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.available-budgets', [$currency->code]).$this->buildParams());

        /** @var AvailableBudgetTransformer $transformer */
        $transformer      = app(AvailableBudgetTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource         = new FractalCollection($availableBudgets, $transformer, 'available_budgets');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listBillByCurrency
     *
     * List all bills
     *
     * @throws FireflyException
     */
    public function bills(TransactionCurrency $currency): JsonResponse
    {
        $manager     = $this->getManager();

        /** @var BillRepositoryInterface $billRepos */
        $billRepos   = app(BillRepositoryInterface::class);
        $pageSize    = $this->parameters->get('limit');
        $unfiltered  = $billRepos->getBills();

        // filter and paginate list:
        $collection  = $unfiltered->filter(
            static fn(Bill $bill) => $bill->transaction_currency_id === $currency->id
        );
        $count       = $collection->count();
        $bills       = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($bills, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.bills', [$currency->code]).$this->buildParams());

        /** @var BillTransformer $transformer */
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($bills, $transformer, 'bills');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listBudgetLimitByCurrency
     *
     * List all budget limits
     *
     * @throws FireflyException
     */
    public function budgetLimits(TransactionCurrency $currency): JsonResponse
    {
        /** @var BudgetLimitRepositoryInterface $blRepository */
        $blRepository = app(BudgetLimitRepositoryInterface::class);

        $manager      = $this->getManager();
        $pageSize     = $this->parameters->get('limit');
        $collection   = $blRepository->getAllBudgetLimitsByCurrency($currency, $this->parameters->get('start'), $this->parameters->get('end'));
        $count        = $collection->count();
        $budgetLimits = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        $paginator    = new LengthAwarePaginator($budgetLimits, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.budget-limits', [$currency->code]).$this->buildParams());

        /** @var BudgetLimitTransformer $transformer */
        $transformer  = app(BudgetLimitTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($budgetLimits, $transformer, 'budget_limits');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listRecurrenceByCurrency
     *
     * List all recurring transactions.
     *
     * @throws FireflyException
     */
    public function recurrences(TransactionCurrency $currency): JsonResponse
    {
        $manager        = $this->getManager();
        // types to get, page size:
        $pageSize       = $this->parameters->get('limit');

        // get list of budgets. Count it and split it.
        /** @var RecurringRepositoryInterface $recurringRepos */
        $recurringRepos = app(RecurringRepositoryInterface::class);
        $unfiltered     = $recurringRepos->get();

        // filter selection
        $collection     = $unfiltered->filter(
            static function (Recurrence $recurrence) use ($currency) {  // @phpstan-ignore-line
                /** @var RecurrenceTransaction $transaction */
                foreach ($recurrence->recurrenceTransactions as $transaction) {
                    if ($transaction->transaction_currency_id === $currency->id || $transaction->foreign_currency_id === $currency->id) {
                        return $recurrence;
                    }
                }

                return null;
            }
        );
        $count          = $collection->count();
        $piggyBanks     = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator      = new LengthAwarePaginator($piggyBanks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.currencies.recurrences', [$currency->code]).$this->buildParams());

        /** @var RecurrenceTransformer $transformer */
        $transformer    = app(RecurrenceTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource       = new FractalCollection($piggyBanks, $transformer, 'recurrences');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listRuleByCurrency
     *
     * List all of them.
     *
     * @throws FireflyException
     */
    public function rules(TransactionCurrency $currency): JsonResponse
    {
        $manager     = $this->getManager();
        $pageSize    = $this->parameters->get('limit');

        // get list of budgets. Count it and split it.
        /** @var RuleRepositoryInterface $ruleRepos */
        $ruleRepos   = app(RuleRepositoryInterface::class);
        $unfiltered  = $ruleRepos->getAll();

        $collection  = $unfiltered->filter(
            static function (Rule $rule) use ($currency) { // @phpstan-ignore-line
                /** @var RuleTrigger $trigger */
                foreach ($rule->ruleTriggers as $trigger) {
                    if ('currency_is' === $trigger->trigger_type && $currency->name === $trigger->trigger_value) {
                        return $rule;
                    }
                }

                return null;
            }
        );

        $count       = $collection->count();
        $rules       = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rules.index').$this->buildParams());

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($rules, $transformer, 'rules');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/currencies/listTransactionByCurrency
     *
     * Show all transactions.
     *
     * @throws FireflyException
     */
    public function transactions(Request $request, TransactionCurrency $currency): JsonResponse
    {
        $pageSize     = $this->parameters->get('limit');
        $type         = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types        = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager      = $this->getManager();

        /** @var User $admin */
        $admin        = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector    = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on currency.
            ->setCurrency($currency)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types)
        ;
        if (null !== $this->parameters->get('start')) {
            $collector->setStart($this->parameters->get('start'));
        }
        if (null !== $this->parameters->get('end')) {
            $collector->setEnd($this->parameters->get('end'));
        }
        $paginator    = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.currencies.transactions', [$currency->code]).$this->buildParams());

        // enrich
        $enrichment   = new TransactionGroupEnrichment();
        $enrichment->setUser($admin);
        $transactions = $enrichment->enrich($paginator->getCollection());

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
