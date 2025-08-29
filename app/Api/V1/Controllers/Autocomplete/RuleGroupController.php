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
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class RuleGroupController
 */
class RuleGroupController extends Controller
{
    private RuleGroupRepositoryInterface $repository;
    protected array $acceptedRoles = [UserRoleEnum::READ_RULES];

    /**
     * RuleGroupController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->repository = app(RuleGroupRepositoryInterface::class);
                $this->repository->setUser($this->user);
                $this->repository->setUserGroup($this->userGroup);

                return $next($request);
            }
        );
    }

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
                'active'      => $group->active,
            ];
        }

        return response()->api($response);
    }
}
