<?php
/**
 * LinkTypeController.php
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

use FireflyIII\Api\V1\Requests\LinkTypeRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\LinkTypeTransformer;
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
 * Class LinkTypeController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LinkTypeController extends Controller
{
    /** @var LinkTypeRepositoryInterface The link type repository */
    private $repository;

    /** @var UserRepositoryInterface The user repository */
    private $userRepository;

    /**
     * LinkTypeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                 = auth()->user();
                $this->repository     = app(LinkTypeRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param LinkType $linkType
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function delete(LinkType $linkType): JsonResponse
    {
        if (false === $linkType->editable) {
            throw new FireflyException(sprintf('You cannot delete this link type (#%d, "%s")', $linkType->id, $linkType->name));
        }
        $this->repository->destroy($linkType, null);

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
        $manager  = new Manager;
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of accounts. Count it and split it.
        $collection = $this->repository->get();
        $count      = $collection->count();
        $linkTypes  = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($linkTypes, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.link_types.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new FractalCollection($linkTypes, new LinkTypeTransformer($this->parameters), 'link_types');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param Request  $request
     * @param LinkType $linkType
     *
     * @return JsonResponse
     */
    public function show(Request $request, LinkType $linkType): JsonResponse
    {
        $manager = new Manager;

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($linkType, new LinkTypeTransformer($this->parameters), 'link_types');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param LinkTypeRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(LinkTypeRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            throw new FireflyException('You need the "owner"-role to do this.');
        }
        $data = $request->getAll();
        // if currency ID is 0, find the currency by the code:
        $linkType = $this->repository->store($data);
        $manager  = new Manager;
        $baseUrl  = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($linkType, new LinkTypeTransformer($this->parameters), 'link_types');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Update object.
     *
     * @param LinkTypeRequest $request
     * @param LinkType        $linkType
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function update(LinkTypeRequest $request, LinkType $linkType): JsonResponse
    {
        if (false === $linkType->editable) {
            throw new FireflyException(sprintf('You cannot edit this link type (#%d, "%s")', $linkType->id, $linkType->name));
        }

        /** @var User $admin */
        $admin = auth()->user();

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            throw new FireflyException('You need the "owner"-role to do this.');
        }

        $data = $request->getAll();
        $this->repository->update($linkType, $data);
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($linkType, new LinkTypeTransformer($this->parameters), 'link_types');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
