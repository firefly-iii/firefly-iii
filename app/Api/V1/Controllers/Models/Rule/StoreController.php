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

namespace FireflyIII\Api\V1\Controllers\Models\Rule;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Rule\StoreRequest;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    private RuleRepositoryInterface $ruleRepository;

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
        $rule    = $this->ruleRepository->store($request->getAll());
        $manager = $this->getManager();
        /** @var RuleTransformer $transformer */
        $transformer = app(RuleTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($rule, $transformer, 'rules');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}