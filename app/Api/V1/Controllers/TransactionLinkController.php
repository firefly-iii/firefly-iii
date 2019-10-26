<?php
/**
 * TransactionLinkController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\TransactionLinkRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TransactionLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class TransactionLinkController
 */
class TransactionLinkController extends Controller
{
    use TransactionFilter;

    /** @var JournalRepositoryInterface The journal repository */
    private $journalRepository;
    /** @var LinkTypeRepositoryInterface The link type repository */
    private $repository;

    /**
     * TransactionLinkController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                $this->repository        = app(LinkTypeRepositoryInterface::class);
                $this->journalRepository = app(JournalRepositoryInterface::class);

                $this->repository->setUser($user);
                $this->journalRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param TransactionJournalLink $link
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function delete(TransactionJournalLink $link): JsonResponse
    {
        $this->repository->destroyLink($link);

        return response()->json([], 204);
    }

    /**
     * List all of them.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = $this->getManager();
        // read type from URI
        $name = $request->get('name');

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $linkType = $this->repository->findByName($name);

        // get list of transaction links. Count it and split it.
        $collection   = $this->repository->getJournalLinks($linkType);
        $count        = $collection->count();
        $journalLinks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($journalLinks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.transaction_links.index') . $this->buildParams());

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($journalLinks, $transformer, 'transaction_links');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param TransactionJournalLink $journalLink
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(TransactionJournalLink $journalLink): JsonResponse
    {
        $manager = $this->getManager();

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param TransactionLinkRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(TransactionLinkRequest $request): JsonResponse
    {
        $manager = $this->getManager();
        $data    = $request->getAll();
        $inward  = $this->journalRepository->findNull($data['inward_id'] ?? 0);
        $outward = $this->journalRepository->findNull($data['outward_id'] ?? 0);
        if (null === $inward || null === $outward) {
            throw new FireflyException(trans('error_source_or_dest_null'));
        }
        $data['direction'] = 'inward';

        $journalLink = $this->repository->storeLink($data, $inward, $outward);

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update object.
     *
     * @param TransactionLinkRequest $request
     * @param TransactionJournalLink $journalLink
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function update(TransactionLinkRequest $request, TransactionJournalLink $journalLink): JsonResponse
    {
        $manager         = $this->getManager();
        $data            = $request->getAll();
        $data['inward']  = $this->journalRepository->findNull($data['inward_id'] ?? 0);
        $data['outward'] = $this->journalRepository->findNull($data['outward_id'] ?? 0);
        if (null === $data['inward'] || null === $data['outward']) {
            throw new FireflyException(trans('api.error_source_or_dest_null'));
        }
        $data['direction'] = 'inward';
        $journalLink       = $this->repository->updateLink($journalLink, $data);

        /** @var TransactionLinkTransformer $transformer */
        $transformer = app(TransactionLinkTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($journalLink, $transformer, 'transaction_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
