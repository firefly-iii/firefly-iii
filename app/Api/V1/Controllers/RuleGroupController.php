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

use Exception;
use FireflyIII\Api\V1\Requests\RuleGroupRequest;
use FireflyIII\Api\V1\Requests\RuleGroupTestRequest;
use FireflyIII\Api\V1\Requests\RuleGroupTriggerRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Log;

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
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
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

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($ruleGroups, $transformer, 'rule_groups');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request $request
     * @param RuleGroup $group
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function rules(Request $request, RuleGroup $group): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of budgets. Count it and split it.
        $collection = $this->ruleGroupRepository->getRules($group);
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rule_groups.rules', [$group->id]) . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

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
     * @param Request $request
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Request $request, RuleGroup $ruleGroup): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

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
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param RuleGroupTestRequest $request
     * @param RuleGroup $group
     *
     * @return JsonResponse
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGroup(RuleGroupTestRequest $request, RuleGroup $group): JsonResponse
    {
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        Log::debug('Now in testGroup()');
        /** @var Collection $rules */
        $rules = $this->ruleGroupRepository->getActiveRules($group);
        if (0 === $rules->count()) {
            throw new FireflyException('No rules in this rule group.');
        }
        $parameters           = $request->getTestParameters();
        $matchingTransactions = [];

        Log::debug(sprintf('Going to test %d rules', $rules->count()));
        /** @var Rule $rule */
        foreach ($rules as $rule) {
            Log::debug(sprintf('Now testing rule #%d, "%s"', $rule->id, $rule->title));
            /** @var TransactionMatcher $matcher */
            $matcher = app(TransactionMatcher::class);
            // set all parameters:
            $matcher->setRule($rule);
            $matcher->setStartDate($parameters['start_date']);
            $matcher->setEndDate($parameters['end_date']);
            $matcher->setSearchLimit($parameters['search_limit']);
            $matcher->setTriggeredLimit($parameters['trigger_limit']);
            $matcher->setAccounts($parameters['accounts']);

            $result = $matcher->findTransactionsByRule();
            /** @noinspection AdditionOperationOnArraysInspection */
            $matchingTransactions = $result + $matchingTransactions;
        }

        // make paginator out of results.
        $count        = count($matchingTransactions);
        $transactions = array_slice($matchingTransactions, ($parameters['page'] - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($transactions, $count, $pageSize, $parameters['page']);
        $paginator->setPath(route('api.v1.rule_groups.test', [$group->id]) . $this->buildParams());

        // resulting list is presented as JSON thing.
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($matchingTransactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Execute the given rule group on a set of existing transactions.
     *
     * @param RuleGroupTriggerRequest $request
     * @param RuleGroup $group
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function triggerGroup(RuleGroupTriggerRequest $request, RuleGroup $group): JsonResponse
    {
        $parameters = $request->getTriggerParameters();

        /** @var Collection $collection */
        $collection = $this->ruleGroupRepository->getActiveRules($group);
        $rules      = [];
        /** @var Rule $item */
        foreach ($collection as $item) {
            $rules[] = $item->id;
        }

        // start looping.
        /** @var RuleEngine $ruleEngine */
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->setUser(auth()->user());
        $ruleEngine->setRulesToApply($rules);
        $ruleEngine->setTriggerMode(RuleEngine::TRIGGER_STORE);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($parameters['accounts']);
        $collector->setRange($parameters['start_date'], $parameters['end_date']);
        $journals = $collector->getExtractedJournals();

        /** @var array $journal */
        foreach ($journals as $journal) {
            Log::debug('Start of new journal.');
            $ruleEngine->processJournalArray($journal);
            Log::debug('Done with all rules for this group + done with journal.');
        }

        return response()->json([], 204);
    }

    /**
     * Update a rule group.
     *
     * @param RuleGroupRequest $request
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
     */
    public function update(RuleGroupRequest $request, RuleGroup $ruleGroup): JsonResponse
    {
        $ruleGroup = $this->ruleGroupRepository->update($ruleGroup, $request->getAll());
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request $request
     * @param RuleGroup $ruleGroup
     * @return JsonResponse
     */
    public function moveDown(Request $request, RuleGroup $ruleGroup): JsonResponse
    {
        $this->ruleGroupRepository->moveDown($ruleGroup);
        $ruleGroup = $this->ruleGroupRepository->find($ruleGroup->id);
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request $request
     * @param RuleGroup $ruleGroup
     * @return JsonResponse
     */
    public function moveUp(Request $request, RuleGroup $ruleGroup): JsonResponse
    {
        $this->ruleGroupRepository->moveUp($ruleGroup);
        $ruleGroup = $this->ruleGroupRepository->find($ruleGroup->id);
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }
}
