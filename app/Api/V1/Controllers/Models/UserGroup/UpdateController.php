<?php

/*
 * UpdateController.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\UserGroup;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\UserGroup\UpdateRequest;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\Transformers\UserGroupTransformer;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    public const string RESOURCE_KEY = 'user_groups';

    private UserGroupRepositoryInterface $repository;

    /**
     * AccountController constructor.
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

    public function update(UpdateRequest $request, UserGroup $userGroup): JsonResponse
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        $data        = $request->getData();
        $userGroup   = $this->repository->update($userGroup, $data);
        $userGroup->refresh();
        app('preferences')->mark();

        $transformer = new UserGroupTransformer();
        $transformer->setParameters($this->parameters);

        return response()
            ->api($this->jsonApiObject(self::RESOURCE_KEY, $userGroup, $transformer))
            ->header('Content-Type', self::CONTENT_TYPE)
        ;
    }
}
