<?php

/**
 * RuleController.php
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
use FireflyIII\Api\V1\Requests\Autocomplete\AutocompleteApiRequest;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class RuleController
 */
class RuleController extends Controller
{
    private RuleRepositoryInterface $repository;
    protected array                 $acceptedRoles = [UserRoleEnum::READ_RULES];

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->validateUserGroup($request);
                $this->repository = app(RuleRepositoryInterface::class);
                $this->repository->setUser($this->user);
                $this->repository->setUserGroup($this->userGroup);

                return $next($request);
            }
        );
    }

    public function rules(AutocompleteApiRequest $request): JsonResponse
    {
        $rules    = $this->repository->searchRule($request->attributes->get('query'), $request->attributes->get('limit'));
        $response = [];

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $response[] = [
                'id'          => (string)$rule->id,
                'name'        => $rule->title,
                'description' => $rule->description,
                'active'      => $rule->active,
            ];
        }

        return response()->api($response);
    }
}
