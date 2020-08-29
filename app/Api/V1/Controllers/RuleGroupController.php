<?php
/**
 * RuleGroupController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

use Exception;
use FireflyIII\Api\V1\Requests\RuleGroupRequest;
use FireflyIII\Api\V1\Requests\RuleGroupTestRequest;
use FireflyIII\Api\V1\Requests\RuleGroupTriggerRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class RuleGroupController
 */
class RuleGroupController extends Controller
{
    /** @var AccountRepositoryInterface Account repository */
    private $accountRepository;

    /** @var RuleGroupRepositoryInterface The rule group repository */
    private $ruleGroupRepository;


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

                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->accountRepository->setUser($user);

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
     * @codeCoverageIgnore
     */
    public function delete(RuleGroup $ruleGroup): JsonResponse
    {
        $this->ruleGroupRepository->destroy($ruleGroup, null);

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
        $pageSize = (int) app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     */
    public function moveDown(RuleGroup $ruleGroup): JsonResponse
    {
        $this->ruleGroupRepository->moveDown($ruleGroup);
        $ruleGroup = $this->ruleGroupRepository->find($ruleGroup->id);
        $manager   = $this->getManager();

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     */
    public function moveUp(RuleGroup $ruleGroup): JsonResponse
    {
        $this->ruleGroupRepository->moveUp($ruleGroup);
        $ruleGroup = $this->ruleGroupRepository->find($ruleGroup->id);
        $manager   = $this->getManager();

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param RuleGroup $group
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function rules(RuleGroup $group): JsonResponse
    {
        $manager = $this->getManager();
        // types to get, page size:
        $pageSize = (int) app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->ruleGroupRepository->getRules($group);
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rule_groups.rules', [$group->id]) . $this->buildParams());

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($rules, $transformer, 'rules');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

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
        $manager   = $this->getManager();

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param RuleGroupTestRequest $request
     * @param RuleGroup            $group
     *
     * @return JsonResponse
     * @throws FireflyException
     *
     */
    public function testGroup(RuleGroupTestRequest $request, RuleGroup $group): JsonResponse
    {
        /** @var Collection $rules */
        $rules = $this->ruleGroupRepository->getActiveRules($group);
        if (0 === $rules->count()) {
            throw new FireflyException('200023: No rules in this rule group.');
        }
        $parameters = $request->getTestParameters();

        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine = app(RuleEngineInterface::class);
        $ruleEngine->setRules($rules);

        // overrule the rule(s) if necessary.
        if (array_key_exists('start', $parameters) && null !== $parameters['start']) {
            // add a range:
            $ruleEngine->addOperator(['type' => 'date_after', 'value' => $parameters['start']->format('Y-m-d')]);
        }

        if (array_key_exists('end', $parameters) && null !== $parameters['end']) {
            // add a range:
            $ruleEngine->addOperator(['type' => 'date_before', 'value' => $parameters['end']->format('Y-m-d')]);
        }
        if (array_key_exists('accounts', $parameters) && '' !== $parameters['accounts']) {
            $ruleEngine->addOperator(['type' => 'account_id', 'value' => $parameters['accounts']]);
        }

        // file the rule(s)
        $transactions = $ruleEngine->find();
        $count        = $transactions->count();

        $paginator = new LengthAwarePaginator($transactions, $count, 31337, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rule_groups.test', [$group->id]) . $this->buildParams());

        // resulting list is presented as JSON thing.
        $manager = $this->getManager();
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Execute the given rule group on a set of existing transactions.
     *
     * @param RuleGroupTriggerRequest $request
     * @param RuleGroup               $group
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function triggerGroup(RuleGroupTriggerRequest $request, RuleGroup $group): JsonResponse
    {
        /** @var Collection $rules */
        $rules = $this->ruleGroupRepository->getActiveRules($group);
        if (0 === $rules->count()) {
            throw new FireflyException('200023: No rules in this rule group.');
        }

        // Get parameters specified by the user
        $parameters = $request->getTriggerParameters();

        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine = app(RuleEngineInterface::class);
        $ruleEngine->setRules($rules);

        // overrule the rule(s) if necessary.
        if (array_key_exists('start', $parameters) && null !== $parameters['start']) {
            // add a range:
            $ruleEngine->addOperator(['type' => 'date_after', 'value' => $parameters['start']->format('Y-m-d')]);
        }

        if (array_key_exists('end', $parameters) && null !== $parameters['end']) {
            // add a range:
            $ruleEngine->addOperator(['type' => 'date_before', 'value' => $parameters['end']->format('Y-m-d')]);
        }
        if (array_key_exists('accounts', $parameters) && '' !== $parameters['accounts']) {
            $ruleEngine->addOperator(['type' => 'account_id', 'value' => $parameters['accounts']]);
        }

        // file the rule(s)
        $ruleEngine->fire();

        return response()->json([], 204);
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
        $manager   = $this->getManager();

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }
}
