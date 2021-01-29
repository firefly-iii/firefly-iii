<?php

/*
 * CreateController.php
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
/*
 * CreateController.php
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

namespace FireflyIII\Api\V1\Controllers\Webhook;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Webhook\CreateRequest;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Transformers\WebhookTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class CreateController
 */
class CreateController extends Controller
{
    private WebhookRepositoryInterface $repository;

    /**
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var WebhookRepositoryInterface repository */
                $this->repository = app(WebhookRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * @param CreateRequest $request
     *
     * @return JsonResponse
     */
    public function store(CreateRequest $request): JsonResponse
    {
        $data    = $request->getData();
        $webhook = $this->repository->store($data);
        $manager = $this->getManager();
        /** @var WebhookTransformer $transformer */
        $transformer = app(WebhookTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($webhook, $transformer, 'webhooks');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

}
