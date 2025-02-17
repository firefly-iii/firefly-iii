<?php

/*
 * TriggerController.php
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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\Rule;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Rule\TestRequest;
use FireflyIII\Api\V1\Requests\Models\Rule\TriggerRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\JsonApi\Enrichments\TransactionGroupEnrichment;
use FireflyIII\TransactionRules\Engine\RuleEngineInterface;
use FireflyIII\Transformers\TransactionGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;

/**
 * Class TriggerController
 */
class TriggerController extends Controller
{
    private RuleRepositoryInterface $ruleRepository;

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                 = auth()->user();

                $this->ruleRepository = app(RuleRepositoryInterface::class);
                $this->ruleRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/rules/testRule
     */
    public function testRule(TestRequest $request, Rule $rule): JsonResponse
    {
        $parameters   = $request->getTestParameters();

        /** @var RuleEngineInterface $ruleEngine */
        $ruleEngine   = app(RuleEngineInterface::class);
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
            $ruleEngine->addOperator(['type' => 'account_id', 'value' => implode(',', $parameters['accounts'])]);
        }

        // file the rule(s)
        $transactions = $ruleEngine->find();
        $count        = $transactions->count();

        // enrich
        $enrichment   = new TransactionGroupEnrichment();
        $enrichment->setUser($rule->user);
        $transactions = $enrichment->enrich($transactions);

        $paginator    = new LengthAwarePaginator($transactions, $count, 31337, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.rules.test', [$rule->id]).$this->buildParams());

        // resulting list is presented as JSON thing.
        $manager      = $this->getManager();

        /** @var TransactionGroupTransformer $transformer */
        $transformer  = app(TransactionGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource     = new FractalCollection($transactions, $transformer, 'transactions');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/rules/fireRule
     *
     * Execute the given rule group on a set of existing transactions.
     */
    public function triggerRule(TriggerRequest $request, Rule $rule): JsonResponse
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
        if (array_key_exists('accounts', $parameters) && is_array($parameters['accounts']) && count($parameters['accounts']) > 0) {
            $ruleEngine->addOperator(['type' => 'account_id', 'value' => implode(',', $parameters['accounts'])]);
        }

        // fire the rule(s)
        $ruleEngine->fire();

        return response()->json([], 204);
    }
}
