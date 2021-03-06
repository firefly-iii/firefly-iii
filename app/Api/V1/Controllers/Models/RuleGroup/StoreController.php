<?php
/*
 * StoreController.php
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
use FireflyIII\Api\V1\Requests\Models\RuleGroup\StoreRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Transformers\RuleGroupTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{

    private AccountRepositoryInterface   $accountRepository;
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

                $this->accountRepository = app(AccountRepositoryInterface::class);
                $this->accountRepository->setUser($user);

                return $next($request);
            }
        );
    }


    /**
     * Store new object.
     *
     * @param StoreRequest $request
     *
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $ruleGroup = $this->ruleGroupRepository->store($request->getAll());
        $manager   = $this->getManager();

        /** @var RuleGroupTransformer $transformer */
        $transformer = app(RuleGroupTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($ruleGroup, $transformer, 'rule_groups');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}