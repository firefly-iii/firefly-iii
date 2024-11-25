<?php

/*
 * UpdateController.php
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

namespace FireflyIII\Api\V1\Controllers\Webhook;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Webhook\UpdateRequest;
use FireflyIII\Models\Webhook;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Transformers\WebhookTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UpdateController
 */
class UpdateController extends Controller
{
    private WebhookRepositoryInterface $repository;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(WebhookRepositoryInterface::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/updateWebhook
     */
    public function update(Webhook $webhook, UpdateRequest $request): JsonResponse
    {
        $data        = $request->getData();
        if (false === config('firefly.allow_webhooks')) {
            Log::channel('audit')->info(sprintf('User tries to update webhook #%d, but webhooks are DISABLED.', $webhook->id), $data);

            throw new NotFoundHttpException('Webhooks are not enabled.');
        }

        $webhook     = $this->repository->update($webhook, $data);
        $manager     = $this->getManager();

        Log::channel('audit')->info(sprintf('User updates webhook #%d', $webhook->id), $data);

        /** @var WebhookTransformer $transformer */
        $transformer = app(WebhookTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($webhook, $transformer, 'webhooks');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
