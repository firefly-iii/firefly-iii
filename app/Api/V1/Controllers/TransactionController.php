<?php

/**
 * TransactionController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\TransactionRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class TransactionController
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionController extends Controller
{

    /** @var JournalRepositoryInterface The journal repository */
    private $repository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var JournalRepositoryInterface repository */
                $this->repository = app(JournalRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \FireflyIII\Models\Transaction $transaction
     *
     * @return JsonResponse
     */
    public function delete(Transaction $transaction): JsonResponse
    {
        $journal = $transaction->transactionJournal;
        $this->repository->destroy($journal);

        return response()->json([], 204);
    }

    /**
     * Show all transactions.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTypes($this->parameters->get('type'));
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var User $admin */
        $admin = auth()->user();
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser($admin);
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();

        if (\in_array(TransactionType::TRANSFER, $types, true)) {
            $collector->removeFilter(InternalTransferFilter::class);
        }

        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $collector->setTypes($types);
        $paginator = $collector->getPaginatedTransactions();
        $paginator->setPath(route('api.v1.transactions.index') . $this->buildParams());
        $transactions = $paginator->getCollection();

        $resource = new FractalCollection($transactions, new TransactionTransformer($this->parameters), 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Show a single transaction.
     *
     * @param Request     $request
     * @param Transaction $transaction
     * @param string      $include
     *
     * @return JsonResponse
     */
    public function show(Request $request, Transaction $transaction, string $include = null): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // add include parameter:
        $include = $include ?? '';
        $include = $request->get('include') ?? $include;
        $manager->parseIncludes($include);

        // collect transactions using the journal collector
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$transaction->transactionJournal]));

        // add filter to remove transactions:
        $transactionType = $transaction->transactionJournal->transactionType->type;
        if ($transactionType === TransactionType::WITHDRAWAL) {
            $collector->addFilter(PositiveAmountFilter::class);
        }
        if (!($transactionType === TransactionType::WITHDRAWAL)) {
            $collector->addFilter(NegativeAmountFilter::class);
        }

        $transactions = $collector->getTransactions();
        $resource     = new FractalCollection($transactions, new TransactionTransformer($this->parameters), 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a new transaction.
     *
     * @param TransactionRequest         $request
     *
     * @param JournalRepositoryInterface $repository
     *
     * @throws FireflyException
     * @return JsonResponse
     */
    public function store(TransactionRequest $request, JournalRepositoryInterface $repository): JsonResponse
    {
        $data         = $request->getAll();
        $data['user'] = auth()->user()->id;
        $journal      = $repository->store($data);

        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        // collect transactions using the journal collector
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));

        // add filter to remove transactions:
        $transactionType = $journal->transactionType->type;
        if ($transactionType === TransactionType::WITHDRAWAL) {
            $collector->addFilter(PositiveAmountFilter::class);
        }
        if (!($transactionType === TransactionType::WITHDRAWAL)) {
            $collector->addFilter(NegativeAmountFilter::class);
        }

        $transactions = $collector->getTransactions();
        $resource     = new FractalCollection($transactions, new TransactionTransformer($this->parameters), 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Update a transaction.
     *
     * @param TransactionRequest         $request
     * @param JournalRepositoryInterface $repository
     * @param Transaction                $transaction
     *
     * @return JsonResponse
     */
    public function update(TransactionRequest $request, JournalRepositoryInterface $repository, Transaction $transaction): JsonResponse
    {
        $data         = $request->getAll();
        $data['user'] = auth()->user()->id;
        $journal      = $repository->update($transaction->transactionJournal, $data);
        $manager      = new Manager();
        $baseUrl      = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        // needs a lot of extra data to match the journal collector. Or just expand that one.
        // collect transactions using the journal collector
        $collector = app(TransactionCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));

        // add filter to remove transactions:
        $transactionType = $journal->transactionType->type;
        if ($transactionType === TransactionType::WITHDRAWAL) {
            $collector->addFilter(PositiveAmountFilter::class);
        }
        if (!($transactionType === TransactionType::WITHDRAWAL)) {
            $collector->addFilter(NegativeAmountFilter::class);
        }

        $transactions = $collector->getTransactions();
        $resource     = new FractalCollection($transactions, new TransactionTransformer($this->parameters), 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * All the types you can request.
     *
     * @param string $type
     *
     * @return array
     */
    private function mapTypes(string $type): array
    {
        $types  = [
            'all'             => [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER, TransactionType::OPENING_BALANCE,
                                  TransactionType::RECONCILIATION,],
            'withdrawal'      => [TransactionType::WITHDRAWAL,],
            'withdrawals'     => [TransactionType::WITHDRAWAL,],
            'expense'         => [TransactionType::WITHDRAWAL,],
            'income'          => [TransactionType::DEPOSIT,],
            'deposit'         => [TransactionType::DEPOSIT,],
            'deposits'        => [TransactionType::DEPOSIT,],
            'transfer'        => [TransactionType::TRANSFER,],
            'transfers'       => [TransactionType::TRANSFER,],
            'opening_balance' => [TransactionType::OPENING_BALANCE,],
            'reconciliation'  => [TransactionType::RECONCILIATION,],
            'reconciliations' => [TransactionType::RECONCILIATION,],
            'special'         => [TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,],
            'specials'        => [TransactionType::OPENING_BALANCE, TransactionType::RECONCILIATION,],
            'default'         => [TransactionType::WITHDRAWAL, TransactionType::DEPOSIT, TransactionType::TRANSFER,],
        ];
        $return = $types['default'];
        if (isset($types[$type])) {
            $return = $types[$type];
        }

        return $return;

    }
}
