<?php
/**
 * RuleGroupController.php
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

use FireflyIII\Api\V1\Requests\RuleGroupRequest;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Transformers\RuleGroupTransformer;
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
 * Class RuleGroupController
 */
class RuleGroupController extends Controller
{
    /** @var RuleGroupRepositoryInterface The rule group repository */
    private $ruleGroupRepository;

    /**
     * RuleGroupController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
                $this->ruleGroupRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     */
    public function delete(RuleGroup $ruleGroup): JsonResponse
    {
        $this->ruleGroupRepository->destroy($ruleGroup, null);

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

        // get list of rule groups. Count it and split it.
        $collection = $this->ruleGroupRepository->get();
        $count      = $collection->count();
        $ruleGroups = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($ruleGroups, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rule_groups.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new FractalCollection($ruleGroups, new RuleGroupTransformer($this->parameters), 'rule_groups');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * List single resource.
     *
     * @param Request   $request
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     */
    public function show(Request $request, RuleGroup $ruleGroup): JsonResponse
    {
        $manager = new Manager();
        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($ruleGroup, new RuleGroupTransformer($this->parameters), 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param RuleGroupRequest $request
     *
     * @return JsonResponse
     */
    public function store(RuleGroupRequest $request): JsonResponse
    {
        $ruleGroup = $this->ruleGroupRepository->store($request->getAll());
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($ruleGroup, new RuleGroupTransformer($this->parameters), 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Update a rule group.
     *
     * @param RuleGroupRequest $request
     * @param RuleGroup        $ruleGroup
     *
     * @return JsonResponse
     */
    public function update(RuleGroupRequest $request, RuleGroup $ruleGroup): JsonResponse
    {
        $ruleGroup = $this->ruleGroupRepository->update($ruleGroup, $request->getAll());
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($ruleGroup, new RuleGroupTransformer($this->parameters), 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
