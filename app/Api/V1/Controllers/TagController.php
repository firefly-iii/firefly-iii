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
use FireflyIII\Api\V1\Requests\DateRequest;
use FireflyIII\Api\V1\Requests\TagRequest;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\TagTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class TagController
 */
class TagController extends Controller
{
    use TransactionFilter;

    /** @var TagRepositoryInterface The tag repository */
    private $repository;

    /**
     * TagController constructor.
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

                $this->repository = app(TagRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * @param DateRequest $request
     *
     * @return JsonResponse
     */
    public function cloud(DateRequest $request): JsonResponse
    {
        // parameters for boxes:
        $dates = $request->getAll();
        $start = $dates['start'];
        $end   = $dates['end'];

        // get all tags:
        $tags  = $this->repository->get();
        $cloud = $this->getTagCloud($tags, $start, $end);

        return response()->json($cloud);
    }

    /**
     * Delete the resource.
     *
     * @param Tag $tag
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function delete(Tag $tag): JsonResponse
    {
        $this->repository->destroy($tag);

        return response()->json([], 204);
    }

    /**
     * List all of them.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $manager = $this->getManager();
        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->repository->get();
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.tags.index') . $this->buildParams());

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
     * @param Tag $tag
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Tag $tag): JsonResponse
    {
        $manager = $this->getManager();
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
        $manager = $this->getManager();
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
     * @codeCoverageIgnore
     */
    public function transactions(Request $request, Tag $tag): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $type     = $request->get('type') ?? 'default';
        $this->parameters->set('type', $type);

        $types   = $this->mapTransactionTypes($this->parameters->get('type'));
        $manager = $this->getManager();
        /** @var User $admin */
        $admin = auth()->user();

        // use new group collector:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector
            ->setUser($admin)
            // filter on tag.
            ->setTag($tag)
            // all info needed for the API:
            ->withAPIInformation()
            // set page size:
            ->setLimit($pageSize)
            // set page to retrieve
            ->setPage($this->parameters->get('page'))
            // set types of transactions to return.
            ->setTypes($types);

        if (null !== $this->parameters->get('start') && null !== $this->parameters->get('end')) {
            $collector->setRange($this->parameters->get('start'), $this->parameters->get('end'));
        }
        $paginator = $collector->getPaginatedGroups();
        $paginator->setPath(route('api.v1.transactions.index') . $this->buildParams());
        $transactions = $paginator->getCollection();

        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
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
        $manager = $this->getManager();
        /** @var TagTransformer $transformer */
        $transformer = app(TagTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'tags');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param array $cloud
     * @param float $min
     * @param float $max
     *
     * @return array
     */
    private function analyseTagCloud(array $cloud, float $min, float $max): array
    {
        foreach (array_keys($cloud['tags']) as $index) {
            $cloud['tags'][$index]['relative'] = round($cloud['tags'][$index]['size'] / $max, 4);
        }
        $cloud['min'] = $min;
        $cloud['max'] = $max;

        return $cloud;
    }

    /**
     * @param Collection $tags
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    private function getTagCloud(Collection $tags, Carbon $start, Carbon $end): array
    {
        $min   = null;
        $max   = 0;
        $cloud = [
            'tags' => [],
        ];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $earned = (float)$this->repository->earnedInPeriod($tag, $start, $end);
            $spent  = (float)$this->repository->spentInPeriod($tag, $start, $end);
            $size   = ($spent * -1) + $earned;
            $min    = $min ?? $size;
            if ($size > 0) {
                $max             = $size > $max ? $size : $max;
                $cloud['tags'][] = [
                    'tag'  => $tag->tag,
                    'id'   => $tag->id,
                    'size' => $size,
                ];
            }
        }
        $cloud = $this->analyseTagCloud($cloud, $min, $max);

        return $cloud;
    }
}
