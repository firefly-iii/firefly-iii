<?php

/*
 * StoreController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\TransactionLink;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\TransactionLink\StoreRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TransactionLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    use TransactionFilter;

    private JournalRepositoryInterface  $journalRepository;
    private LinkTypeRepositoryInterface $repository;

    /**
     * TransactionLinkController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                    = auth()->user();

                $this->repository        = app(LinkTypeRepositoryInterface::class);
                $this->journalRepository = app(JournalRepositoryInterface::class);

                $this->repository->setUser($user);
                $this->journalRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/links/storeTransactionLink
     *
     * Store new object.
     *
     * @throws FireflyException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $manager           = $this->getManager();
        $data              = $request->getAll();
        $inward            = $this->journalRepository->find($data['inward_id'] ?? 0);
        $outward           = $this->journalRepository->find($data['outward_id'] ?? 0);
        if (!$inward instanceof TransactionJournal || !$outward instanceof TransactionJournal) {
            throw new FireflyException('200024: Source or destination does not exist.');
        }
        $data['direction'] = 'inward';

        $journalLink       = $this->repository->storeLink($data, $inward, $outward);

        /** @var TransactionLinkTransformer $transformer */
        $transformer       = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource          = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
