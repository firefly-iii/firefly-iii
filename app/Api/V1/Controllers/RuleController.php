<?php
/**
 * RuleController.php
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
use FireflyIII\Api\V1\Requests\RuleRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\TransactionRules\TransactionMatcher;
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
 * Class RuleController
 */
class RuleController extends Controller
{
    /** @var AccountRepositoryInterface Account repository */
    private $accountRepository;
    /** @var RuleRepositoryInterface The rule repository */
    private $ruleRepository;

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

                $this->ruleRepository = app(RuleRepositoryInterface::class);
                $this->ruleRepository->setUser($user);

                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->accountRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param Rule $rule
     *
     * @return JsonResponse
     */
    public function delete(Rule $rule): JsonResponse
    {
        $this->ruleRepository->destroy($rule);

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
        $collection = $this->ruleRepository->getAll();
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rules.index') . $this->buildParams());

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
     * @param Rule    $rule
     *
     * @return JsonResponse
     */
    public function show(Request $request, Rule $rule): JsonResponse
    {
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param RuleRequest $request
     *
     * @return JsonResponse
     */
    public function store(RuleRequest $request): JsonResponse
    {
        $rule    = $this->ruleRepository->store($request->getAll());
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param Request $request
     * @param Rule    $rule
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function testRule(Request $request, Rule $rule): JsonResponse
    {
        $pageSize     = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $page         = 0 === (int)$request->query('page') ? 1 : (int)$request->query('page');
        $startDate    = null === $request->query('start_date') ? null : Carbon::createFromFormat('Y-m-d', $request->query('start_date'));
        $endDate      = null === $request->query('end_date') ? null : Carbon::createFromFormat('Y-m-d', $request->query('end_date'));
        $searchLimit  = 0 === (int)$request->query('search_limit') ? (int)config('firefly.test-triggers.limit') : (int)$request->query('search_limit');
        $triggerLimit = 0 === (int)$request->query('triggered_limit') ? (int)config('firefly.test-triggers.range') : (int)$request->query('triggered_limit');
        $accountList  = '' === (string)$request->query('accounts') ? [] : explode(',', $request->query('accounts'));
        $accounts     = new Collection;

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

        /** @var Rule $rule */
        Log::debug(sprintf('Now testing rule #%d, "%s"', $rule->id, $rule->title));
        /** @var TransactionMatcher $matcher */
        $matcher = app(TransactionMatcher::class);
        // set all parameters:
        $matcher->setRule($rule);
        $matcher->setStartDate($startDate);
        $matcher->setEndDate($endDate);
        $matcher->setSearchLimit($searchLimit);
        $matcher->setTriggeredLimit($triggerLimit);
        $matcher->setAccounts($accounts);

        $matchingTransactions = $matcher->findTransactionsByRule();
        $matchingTransactions = $matchingTransactions->unique('id');

        // make paginator out of results.
        $count        = $matchingTransactions->count();
        $transactions = $matchingTransactions->slice(($page - 1) * $pageSize, $pageSize);
        // make paginator:
        $paginator = new LengthAwarePaginator($transactions, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rules.test', [$rule->id]) . $this->buildParams());

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
     * @param Request $request
     * @param Rule    $rule
     *
     * @return JsonResponse
     */
    public function triggerRule(Request $request, Rule $rule): JsonResponse
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
            if (null !== $account && $this->accountRepository->isAsset($account)) {
                Log::debug(sprintf('Found account #%d ("%s") and its an asset account', $account->id, $account->name));
                $accounts->push($account);
            }
            if (null === $account) {
                Log::debug(sprintf('No asset account with id "%s"', $accountId));
            }
        }

        // Create a job to do the work asynchronously
        $job = new ExecuteRuleOnExistingTransactions($rule);

        // Apply parameters to the job
        $job->setUser($user);
        $job->setAccounts($accounts);
        $job->setStartDate($startDate);
        $job->setEndDate($endDate);

        // Dispatch a new job to execute it in a queue
        $this->dispatch($job);

        return response()->json([], 204);
    }

    /**
     * Update a rule.
     *
     * @param RuleRequest $request
     * @param Rule        $rule
     *
     * @return JsonResponse
     */
    public function update(RuleRequest $request, Rule $rule): JsonResponse
    {
        $rule    = $this->ruleRepository->update($rule, $request->getAll());
        $manager = new Manager();
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
