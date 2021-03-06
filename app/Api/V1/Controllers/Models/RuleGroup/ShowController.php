<?php
/*
 * ShowController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\RuleGroup;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private RuleGroupRepositoryInterface $ruleGroupRepository;


    /**
     * RuleGroupController constructor.
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

                $this->ruleGroupRepository = app(RuleGroupRepositoryInterface::class);
                $this->ruleGroupRepository->setUser($user);

                return $next($request);
            }
        );
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

        // get list of rule groups. Count it and split it.
        $collection = $this->ruleGroupRepository->get();
        $count      = $collection->count();
        $ruleGroups = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($ruleGroups, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rule_groups.index') . $this->buildParams());

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($ruleGroups, $transformer, 'rule_groups');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }


    /**
     * List single resource.
     *
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(RuleGroup $ruleGroup): JsonResponse
    {
        $manager = $this->getManager();
        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }

}