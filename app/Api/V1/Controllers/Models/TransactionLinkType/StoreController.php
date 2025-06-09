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

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers\Models\TransactionLinkType;

use Illuminate\Support\Facades\Validator;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\TransactionLinkType\StoreRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\Transformers\LinkTypeTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use League\Fractal\Resource\Item;

/**
 * Class StoreController
 */
class StoreController extends Controller
{
    use TransactionFilter;

    private LinkTypeRepositoryInterface $repository;
    private UserRepositoryInterface     $userRepository;

    /**
     * LinkTypeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user                 = auth()->user();
                $this->repository     = app(LinkTypeRepositoryInterface::class);
                $this->userRepository = app(UserRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/links/storeLinkType
     *
     * Store new object.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(StoreRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin       = auth()->user();
        $rules       = ['name' => 'required'];

        if (!$this->userRepository->hasRole($admin, 'owner')) {
            // access denied:
            $messages = ['name' => '200005: You need the "owner" role to do this.'];
            Validator::make([], $rules, $messages)->validate();
        }
        $data        = $request->getAll();
        // if currency ID is 0, find the currency by the code:
        $linkType    = $this->repository->store($data);
        $manager     = $this->getManager();

        /** @var LinkTypeTransformer $transformer */
        $transformer = app(LinkTypeTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource    = new Item($linkType, $transformer, 'link_types');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
