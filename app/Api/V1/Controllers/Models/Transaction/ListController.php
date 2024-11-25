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

namespace FireflyIII\Api\V1\Controllers\Models\Transaction;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalAPIRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\PiggyBankEventTransformer;
use FireflyIII\Transformers\TransactionLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class ListController
 */
class ListController extends Controller
{
    private JournalAPIRepositoryInterface $journalAPIRepository;

    /**
     * TransactionController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin                      = auth()->user();

                $this->journalAPIRepository = app(JournalAPIRepositoryInterface::class);
                $this->journalAPIRepository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/listAttachmentByTransaction
     *
     * @throws FireflyException
     */
    public function attachments(TransactionGroup $transactionGroup): JsonResponse
    {
        $manager     = $this->getManager();
        $pageSize    = $this->parameters->get('limit');
        $collection  = new Collection();
        foreach ($transactionGroup->transactionJournals as $transactionJournal) {
            $collection = $this->journalAPIRepository->getAttachments($transactionJournal)->merge($collection);
        }

        $count       = $collection->count();
        $attachments = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($attachments, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.transactions.attachments', [$transactionGroup->id]).$this->buildParams());

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($attachments, $transformer, 'attachments');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/listEventByTransaction
     *
     * @throws FireflyException
     */
    public function piggyBankEvents(TransactionGroup $transactionGroup): JsonResponse
    {
        $manager     = $this->getManager();
        $collection  = new Collection();
        $pageSize    = $this->parameters->get('limit');
        foreach ($transactionGroup->transactionJournals as $transactionJournal) {
            $collection = $this->journalAPIRepository->getPiggyBankEvents($transactionJournal)->merge($collection);
        }
        $count       = $collection->count();
        $events      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);
        // make paginator:
        $paginator   = new LengthAwarePaginator($events, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.transactions.piggy-bank-events', [$transactionGroup->id]).$this->buildParams());

        /** @var PiggyBankEventTransformer $transformer */
        $transformer = app(PiggyBankEventTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($events, $transformer, 'piggy_bank_events');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        //        /** @var PiggyBankEventTransformer $transformer */
        //        $transformer = app(PiggyBankEventTransformer::class);
        //        $transformer->setParameters($this->parameters);
        //
        //        $resource = new FractalCollection($events, $transformer, 'piggy_bank_events');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/transactions/listLinksByJournal
     *
     * @throws FireflyException
     */
    public function transactionLinks(TransactionJournal $transactionJournal): JsonResponse
    {
        $manager      = $this->getManager();
        $collection   = $this->journalAPIRepository->getJournalLinks($transactionJournal);
        $pageSize     = $this->parameters->get('limit');
        $count        = $collection->count();
        $journalLinks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator    = new LengthAwarePaginator($journalLinks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.transaction-journals.transaction-links', [$transactionJournal->id]).$this->buildParams());

        /** @var TransactionLinkTransformer $transformer */
        $transformer  = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($journalLinks, $transformer, 'transaction_links');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
