<?php


/*
 * DestroyController.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\UserGroup;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private UserGroupRepositoryInterface $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(UserGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request   $request
     * @param UserGroup $userGroup
     *
     * @return JsonResponse
     */
    public function destroy(Request $request, UserGroup $userGroup): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        // need owner role or system owner role to delete user group.
        $access = $user->hasRoleInGroup($userGroup, UserRoleEnum::OWNER, false, true);
        if (false === $access) {
            throw new NotFoundHttpException();
        }
        $this->repository->destroy($userGroup);
        return response()->json([], 204);
    }
}
