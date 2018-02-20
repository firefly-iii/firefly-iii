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
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Preferences;

/**
 * Class TransactionController
 */
class TransactionController extends Controller
{

    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     * TransactionController constructor.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var JournalRepositoryInterface repository */
                $this->repository = app(JournalRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \FireflyIII\Models\Transaction $transaction
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Transaction $transaction)
    {
        $journal = $transaction->transactionJournal;
        $this->repository->delete($journal);

        return response()->json([], 204);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function index(Request $request)
    {
        $pageSize = intval(Preferences::getForUser(auth()->user(), 'listPageSize', 50)->data);

        // read type from URI
        $type = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        // types to get, page size:
        $types = $this->mapTypes($this->parameters->get('type'));

        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // collect transactions using the journal collector
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();

        // remove internal transfer filter:
        if (in_array(TransactionType::TRANSFER, $types)) {
            $collector->removeFilter(InternalTransferFilter::class);
        }

        if (!is_null($this->parameters->get('start')) && !is_null($this->parameters->get('end'))) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $collector->setLimit($pageSize)->setPage($this->parameters->get('page'));
        $collector->setTypes($types);
        $paginator = $collector->getPaginatedJournals();
        $paginator->setPath(route('api.v1.transactions.index') . $this->buildParams());
        $transactions = $paginator->getCollection();


        $resource = new FractalCollection($transactions, new TransactionTransformer($this->parameters), 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * @param Request     $request
     * @param Transaction $transaction
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Transaction $transaction)
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        // needs a lot of extra data to match the journal collector. Or just expand that one.
        // collect transactions using the journal collector
        $collector = app(JournalCollectorInterface::class);
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

        $transactions = $collector->getJournals();
        $resource = new Item($transactions->first(), new TransactionTransformer($this->parameters), 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param TransactionRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function store(TransactionRequest $request)
    {
        $data         = $request->getAll();
        $data['user'] = auth()->user()->id;
        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser(auth()->user());
        $journal = $factory->create($data);

        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        // needs a lot of extra data to match the journal collector. Or just expand that one.
        // collect transactions using the journal collector
        $collector = app(JournalCollectorInterface::class);
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

        $transactions = $collector->getJournals();
        $resource     = new FractalCollection($transactions, new TransactionTransformer($this->parameters), 'transactions');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }


    /**
     * @param BillRequest $request
     * @param Bill        $bill
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(BillRequest $request, Bill $bill)
    {
        die('todo');
        $data    = $request->getAll();
        $bill    = $this->repository->update($bill, $data);
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($bill, new BillTransformer($this->parameters), 'bills');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param string $type
     *
     * @return array
     */
    private function mapTypes(string $type): array
    {
        $types = [
            'all'             => [
                TransactionType::WITHDRAWAL,
                TransactionType::DEPOSIT,
                TransactionType::TRANSFER,
                TransactionType::OPENING_BALANCE,
                TransactionType::RECONCILIATION,
            ],
            'withdrawal'      => [
                TransactionType::WITHDRAWAL,
            ],
            'withdrawals'     => [
                TransactionType::WITHDRAWAL,
            ],
            'expense'         => [
                TransactionType::WITHDRAWAL,
            ],
            'income'          => [
                TransactionType::DEPOSIT,
            ],
            'deposit'         => [
                TransactionType::DEPOSIT,
            ],
            'deposits'        => [
                TransactionType::DEPOSIT,
            ],
            'transfer'        => [
                TransactionType::TRANSFER,
            ],
            'transfers'       => [
                TransactionType::TRANSFER,
            ],
            'opening_balance' => [
                TransactionType::OPENING_BALANCE,
            ],
            'reconciliation'  => [
                TransactionType::RECONCILIATION,
            ],
            'reconciliations' => [
                TransactionType::RECONCILIATION,
            ],
            'special'         => [
                TransactionType::OPENING_BALANCE,
                TransactionType::RECONCILIATION,
            ],
            'specials'        => [
                TransactionType::OPENING_BALANCE,
                TransactionType::RECONCILIATION,
            ],
            'default'         => [
                TransactionType::WITHDRAWAL,
                TransactionType::DEPOSIT,
                TransactionType::TRANSFER,
            ],
        ];
        if (isset($types[$type])) {
            return $types[$type];
        }

        return $types['default']; // @codeCoverageIgnore

    }
}