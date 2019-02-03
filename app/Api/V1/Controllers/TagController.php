<?php
/**
 * TagController.php
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

use Carbon\Carbon;
use FireflyIII\Api\V1\Requests\TagRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TagTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class TagController
 */
class TagController extends Controller
{
    use TransactionFilter;

    /** @var TagRepositoryInterface The tag repository */
    private $repository;

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                $this->repository = app(TagRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function cloud(Request $request): JsonResponse
    {
        // parameters for cloud:
        $start = (string)$request->get('start');
        $end   = (string)$request->get('end');
        if ('' === $start || '' === $end) {
            throw new FireflyException('Start and end are mandatory parameters.');
        }
        $start = Carbon::createFromFormat('Y-m-d', $start);
        $end   = Carbon::createFromFormat('Y-m-d', $end);

        // get all tags:
        $tags   = $this->repository->get();
        $min    = null;
        $max    = 0;
        $return = [
            'tags' => [],
        ];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $earned = (float)$this->repository->earnedInPeriod($tag, $start, $end);
            $spent  = (float)$this->repository->spentInPeriod($tag, $start, $end);
            $size   = ($spent * -1) + $earned;
            $min    = $min ?? $size;
            if ($size > 0) {
                $max              = $size > $max ? $size : $max;
                $return['tags'][] = [
                    'tag'  => $tag->tag,
                    'id'   => $tag->id,
                    'size' => $size,
                ];
            }
        }
        foreach ($return['tags'] as $index => $info) {
            $return['tags'][$index]['relative'] = $return['tags'][$index]['size'] / $max;
        }
        $return['min'] = $min;
        $return['max'] = $max;


        return response()->json($return);
    }

    /**
     * Delete the resource.
     *
     * @param Tag $tag
     *
     * @return JsonResponse
     */
    public function delete(Tag $tag): JsonResponse
    {
        $this->repository->destroy($tag);

        return response()->json([], 204);
    }

    /**
     * List all of them.
     *
     * @param Request $request
     *
     * @return JsonResponse]
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->get();
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.tags.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var TagTransformer $transformer */
        $transformer = app(TagTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($rules, $transformer, 'tags');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * List single resource.
     *
     * @param Request $request
     * @param Tag     $tag
     *
     * @return JsonResponse
     */
    public function show(Request $request, Tag $tag): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var TagTransformer $transformer */
        $transformer = app(TagTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($tag, $transformer, 'tags');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param TagRequest $request
     *
     * @return JsonResponse
     */
    public function store(TagRequest $request): JsonResponse
    {
        $rule    = $this->repository->store($request->getAll());
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var TagTransformer $transformer */
        $transformer = app(TagTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'tags');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show all transactions.
     *
     * @param Request $request
     * @param Tag     $tag
     *
     * @return JsonResponse
     */
    public function transactions(Request $request, Tag $tag): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
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
        $collector->setTag($tag);

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

        /** @var TransactionTransformer $transformer */
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a rule.
     *
     * @param TagRequest $request
     * @param Tag        $tag
     *
     * @return JsonResponse
     */
    public function update(TagRequest $request, Tag $tag): JsonResponse
    {
        $rule    = $this->repository->update($tag, $request->getAll());
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        /** @var TagTransformer $transformer */
        $transformer = app(TagTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'tags');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
