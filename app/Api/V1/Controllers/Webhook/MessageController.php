<?php

/*
 * MessageController.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Transformers\WebhookMessageTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class MessageController
 */
class MessageController extends Controller
{
    public const string RESOURCE_KEY = 'webhook_messages';
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
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/getWebhookMessages
     *
     * @throws FireflyException
     */
    public function index(Webhook $webhook): JsonResponse
    {
        if (false === config('firefly.allow_webhooks')) {
            Log::channel('audit')->warning(sprintf('User tries to view messages of webhook #%d, but webhooks are DISABLED.', $webhook->id));

            throw new NotFoundHttpException('Webhooks are not enabled.');
        }
        Log::channel('audit')->info(sprintf('User views messages of webhook #%d.', $webhook->id));
        $manager     = $this->getManager();
        $pageSize    = $this->parameters->get('limit');
        $collection  = $this->repository->getMessages($webhook);

        $count       = $collection->count();
        $messages    = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($messages, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.webhooks.messages.index', [$webhook->id]).$this->buildParams());

        /** @var WebhookMessageTransformer $transformer */
        $transformer = app(WebhookMessageTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($messages, $transformer, 'webhook_messages');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/getSingleWebhookMessage
     *
     * Show single instance.
     *
     * @throws FireflyException
     */
    public function show(Webhook $webhook, WebhookMessage $message): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('200040: Webhook and webhook message are no match');
        }
        if (false === config('firefly.allow_webhooks')) {
            Log::channel('audit')->warning(sprintf('User tries to view message #%d of webhook #%d, but webhooks are DISABLED.', $message->id, $webhook->id));

            throw new NotFoundHttpException('Webhooks are not enabled.');
        }

        Log::channel('audit')->info(sprintf('User views message #%d of webhook #%d.', $message->id, $webhook->id));

        $manager     = $this->getManager();

        /** @var WebhookMessageTransformer $transformer */
        $transformer = app(WebhookMessageTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource    = new Item($message, $transformer, self::RESOURCE_KEY);

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
