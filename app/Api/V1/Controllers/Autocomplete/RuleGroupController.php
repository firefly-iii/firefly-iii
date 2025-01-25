<?php

/**
 * RuleGroupController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Api\V1\Controllers\Autocomplete;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteRequest;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class RuleGroupController
 */
class RuleGroupController extends Controller
{
    private RuleGroupRepositoryInterface $repository;

    /**
     * RuleGroupController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(RuleGroupRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/autocomplete/getRuleGroupsAC
     */
    public function ruleGroups(AutocompleteRequest $request): JsonResponse
    {
        $data     = $request->getData();
        $groups   = $this->repository->searchRuleGroup($data['query'], $this->parameters->get('limit'));
        $response = [];

        /** @var RuleGroup $group */
        foreach ($groups as $group) {
            $response[] = [
                'id'          => (string) $group->id,
                'name'        => $group->title,
                'description' => $group->description,
            ];
        }

        return response()->api($response);
    }
}
