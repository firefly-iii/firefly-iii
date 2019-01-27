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

use Carbon\Carbon;
use FireflyIII\Api\V1\Requests\RuleGroupRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionTransformer;
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

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($ruleGroups, $transformer, 'rule_groups');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request   $request
     * @param RuleGroup $group
     *
     * @return JsonResponse
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
     * @param Request   $request
     * @param RuleGroup $ruleGroup
     *
     * @return JsonResponse
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
     * @param Request   $request
     * @param RuleGroup $group
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function testGroup(Request $request, RuleGroup $group): JsonResponse
    {
        Log::debug('Now in testGroup()');
        /** @var Collection $rules */
        $rules = $this->ruleGroupRepository->getActiveRules($group);
        if (0 === $rules->count()) {
            throw new FireflyException('No rules in this rule group.');
        }
        $parameters           = $this->getTestParameters($request);
        $accounts             = $this->getAccountParameter($parameters['account_list']);
        $matchingTransactions = new Collection;

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
            $matcher->setAccounts($accounts);

            $result               = $matcher->findTransactionsByRule();
            $matchingTransactions = $result->merge($matchingTransactions);
        }
        $matchingTransactions = $matchingTransactions->unique('id');

        // make paginator out of results.
        $count        = $matchingTransactions->count();
        $transactions = $matchingTransactions->slice(($parameters['page'] - 1) * $parameters['page_size'], $parameters['page_size']);
        // make paginator:
        $paginator = new LengthAwarePaginator($transactions, $count, $parameters['page_size'], $parameters['page']);
        $paginator->setPath(route('api.v1.rule_groups.test', [$group->id]) . $this->buildParams());

        // resulting list is presented as JSON thing.
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var TransactionTransformer $transformer */
        $transformer = app(TransactionTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($matchingTransactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Execute the given rule group on a set of existing transactions.
     *
     * @param Request   $request
     * @param RuleGroup $group
     *
     * @return JsonResponse
     */
    public function triggerGroup(Request $request, RuleGroup $group): JsonResponse
    {
        // Get parameters specified by the user
        /** @var User $user */
        $user        = auth()->user();
        $startDate   = new Carbon($request->get('start_date'));
        $endDate     = new Carbon($request->get('end_date'));
        $accountList = '' === (string)$request->query('accounts') ? [] : explode(',', $request->query('accounts'));
        $accounts    = new Collection;

        foreach ($accountList as $accountId) {
            Log::debug(sprintf('Searching for asset account with id "%s"', $accountId));
            $account = $this->accountRepository->findNull((int)$accountId);
            if (null !== $account && AccountType::ASSET === $account->accountType->type) {
                Log::debug(sprintf('Found account #%d ("%s") and its an asset account', $account->id, $account->name));
                $accounts->push($account);
            }
            if (null === $account) {
                Log::debug(sprintf('No asset account with id "%s"', $accountId));
            }
        }

        /** @var Collection $rules */
        $rules = $this->ruleGroupRepository->getActiveRules($group);
        foreach ($rules as $rule) {
            // Create a job to do the work asynchronously
            $job = new ExecuteRuleOnExistingTransactions($rule);

            // Apply parameters to the job
            $job->setUser($user);
            $job->setAccounts($accounts);
            $job->setStartDate($startDate);
            $job->setEndDate($endDate);

            // Dispatch a new job to execute it in a queue
            $this->dispatch($job);
        }

        return response()->json([], 204);
    }

    /**
     * Update a rule group.
     * TODO update order of rule group
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

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param array $accounts
     *
     * @return Collection
     */
    private function getAccountParameter(array $accounts): Collection
    {
        $return = new Collection;
        foreach ($accounts as $accountId) {
            Log::debug(sprintf('Searching for asset account with id "%s"', $accountId));
            $account = $this->accountRepository->findNull((int)$accountId);
            if (null !== $account && AccountType::ASSET === $account->accountType->type) {
                Log::debug(sprintf('Found account #%d ("%s") and its an asset account', $account->id, $account->name));
                $return->push($account);
            }
            if (null === $account) {
                Log::debug(sprintf('No asset account with id "%s"', $accountId));
            }
        }

        return $return;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getTestParameters(Request $request): array
    {
        return [
            'page_size'     => (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data,
            'page'          => 0 === (int)$request->query('page') ? 1 : (int)$request->query('page'),
            'start_date'    => null === $request->query('start_date') ? null : Carbon::createFromFormat('Y-m-d', $request->query('start_date')),
            'end_date'      => null === $request->query('end_date') ? null : Carbon::createFromFormat('Y-m-d', $request->query('end_date')),
            'search_limit'  => 0 === (int)$request->query('search_limit') ? (int)config('firefly.test-triggers.limit') : (int)$request->query('search_limit'),
            'trigger_limit' => 0 === (int)$request->query('triggered_limit')
                ? (int)config('firefly.test-triggers.range')
                : (int)$request->query(
                    'triggered_limit'
                ),
            'account_list'  => '' === (string)$request->query('accounts') ? [] : explode(',', $request->query('accounts')),
        ];
    }
}
