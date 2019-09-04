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

use FireflyIII\Api\V1\Requests\RuleStoreRequest;
use FireflyIII\Api\V1\Requests\RuleTestRequest;
use FireflyIII\Api\V1\Requests\RuleTriggerRequest;
use FireflyIII\Api\V1\Requests\RuleUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngine;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use Log;

/**
 * Class RuleController
 *
 */
class RuleController extends Controller
{
    /** @var AccountRepositoryInterface Account repository */
    private $accountRepository;
    /** @var RuleRepositoryInterface The rule repository */
    private $ruleRepository;

    /**
     * RuleController constructor.
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
     * @codeCoverageIgnore
     */
    public function delete(Rule $rule): JsonResponse
    {
        $this->ruleRepository->destroy($rule);

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
        $collection = $this->ruleRepository->getAll();
        $count      = $collection->count();
        $rules      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($rules, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rules.index') . $this->buildParams());

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($rules, $transformer, 'rules');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param Rule $rule
     *
     * @return JsonResponse
     */
    public function moveDown(Rule $rule): JsonResponse
    {
        $this->ruleRepository->moveDown($rule);
        $rule    = $this->ruleRepository->find($rule->id);
        $manager = $this->getManager();

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @param Rule $rule
     *
     * @return JsonResponse
     */
    public function moveUp(Rule $rule): JsonResponse
    {
        $this->ruleRepository->moveUp($rule);
        $rule    = $this->ruleRepository->find($rule->id);
        $manager = $this->getManager();

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param Rule $rule
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(Rule $rule): JsonResponse
    {
        $manager = $this->getManager();
        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param RuleStoreRequest $request
     *
     * @return JsonResponse
     */
    public function store(RuleStoreRequest $request): JsonResponse
    {
        $rule    = $this->ruleRepository->store($request->getAll());
        $manager = $this->getManager();
        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @param RuleTestRequest $request
     * @param Rule            $rule
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function testRule(RuleTestRequest $request, Rule $rule): JsonResponse
    {
        $pageSize   = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $parameters = $request->getTestParameters();
        /** @var Rule $rule */
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

        $matchingTransactions = $matcher->findTransactionsByRule();
        $count                = count($matchingTransactions);
        $transactions         = array_slice($matchingTransactions, ($parameters['page'] - 1) * $pageSize, $pageSize);
        $paginator            = new LengthAwarePaginator($transactions, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rules.test', [$rule->id]) . $this->buildParams());

        // resulting list is presented as JSON thing.
        $manager = $this->getManager();
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($matchingTransactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Execute the given rule group on a set of existing transactions.
     *
     * @param RuleTriggerRequest $request
     * @param Rule               $rule
     *
     * @return JsonResponse
     */
    public function triggerRule(RuleTriggerRequest $request, Rule $rule): JsonResponse
    {
        // Get parameters specified by the user
        $parameters = $request->getTriggerParameters();

        /** @var RuleEngine $ruleEngine */
        $ruleEngine = app(RuleEngine::class);
        $ruleEngine->setUser(auth()->user());

        $rules = [$rule->id];

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
     * Update a rule.
     *
     * @param RuleUpdateRequest $request
     * @param Rule              $rule
     *
     * @return JsonResponse
     */
    public function update(RuleUpdateRequest $request, Rule $rule): JsonResponse
    {
        $data    = $request->getAll();
        $rule    = $this->ruleRepository->update($rule, $data);
        $manager = $this->getManager();

        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }
}
