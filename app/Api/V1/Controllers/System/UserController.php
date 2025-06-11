<?php

/*
 * UserController.php
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

namespace FireflyIII\Api\V1\Controllers\System;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\System\UserStoreRequest;
use FireflyIII\Api\V1\Requests\System\UserUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\UserTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class UserController.
 */
class UserController extends Controller
{
    private UserRepositoryInterface $repository;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/users/deleteUser
     *
     * Remove the specified resource from storage.
     *
     * @throws FireflyException
     */
    public function destroy(User $user): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();
        if ($admin->id === $user->id) {
            return response()->json([], 500);
        }

        if ($this->repository->hasRole($admin, 'owner')) {
            $this->repository->destroy($user);

            return response()->json([], 204);
        }

        throw new FireflyException('200025: No access to function.');
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/users/listUser
     *
     * Display a listing of the resource.
     *
     * @throws FireflyException
     */
    public function index(): JsonResponse
    {
        // user preferences
        $pageSize    = $this->parameters->get('limit');
        $manager     = $this->getManager();

        // build collection
        $collection  = $this->repository->all();
        $count       = $collection->count();
        $users       = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($users, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.users.index').$this->buildParams());

        // make resource
        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($users, $transformer, 'users');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/users/getUser
     *
     * Show a single user.
     */
    public function show(User $user): JsonResponse
    {
        // make manager
        $manager     = $this->getManager();

        // make resource
        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($user, $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/users/storeUser
     *
     * Store a new user.
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $data        = $request->getAll();
        $user        = $this->repository->store($data);
        $manager     = $this->getManager();

        // make resource

        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($user, $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/users/updateUser
     *
     * Update a user.
     */
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $data        = $request->getAll();

        // can only update 'blocked' when user is admin.
        if (!$this->repository->hasRole(auth()->user(), 'owner')) {
            Log::debug('Quietly drop fields "blocked" and "blocked_code" from request.');
            unset($data['blocked'], $data['blocked_code']);
        }

        $user        = $this->repository->update($user, $data);
        $manager     = $this->getManager();

        // make resource
        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($user, $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
