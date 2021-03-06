<?php
/**
 * RuleController.php
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

use FireflyIII\Api\V1\Requests\RuleStoreRequest;
use FireflyIII\Api\V1\Requests\RuleTestRequest;
use FireflyIII\Api\V1\Requests\RuleTriggerRequest;
use FireflyIII\Api\V1\Requests\RuleUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
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
        $pageSize = (int) app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
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
        $parameters = $request->getTestParameters();

        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine = app(RuleEngineInterface::class);
        $ruleEngine->setRules(new Collection([$rule]));


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
        $paginator->setPath(route('api.v1.rules.test', [$rule->id]) . $this->buildParams());

        // resulting list is presented as JSON thing.
        $manager = $this->getManager();
        /** @var TransactionGroupTransformer $transformer */
        $transformer = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
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

        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine = app(RuleEngineInterface::class);
        $ruleEngine->setRules(new Collection([$rule]));

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

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
