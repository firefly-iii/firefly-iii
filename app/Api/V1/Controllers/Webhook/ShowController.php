<?php

/*
 * ShowController.php
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
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\Webhook;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Transformers\WebhookTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    public const string RESOURCE_KEY = 'webhooks';
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
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/listWebhook
     *
     * Display a listing of the webhooks of the user.
     *
     * @throws FireflyException
     */
    public function index(): JsonResponse
    {
        if (false === config('firefly.allow_webhooks')) {
            Log::channel('audit')->info('User tries to view all webhooks, but webhooks are DISABLED.');

            throw new NotFoundHttpException('Webhooks are not enabled.');
        }

        Log::channel('audit')->info('User views all webhooks.');
        $manager     = $this->getManager();
        $collection  = $this->repository->all();
        $pageSize    = $this->parameters->get('limit');
        $count       = $collection->count();
        $webhooks    = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator   = new LengthAwarePaginator($webhooks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.webhooks.index').$this->buildParams());

        /** @var WebhookTransformer $transformer */
        $transformer = app(WebhookTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new FractalCollection($webhooks, $transformer, self::RESOURCE_KEY);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/getWebhook
     *
     * Show single instance.
     */
    public function show(Webhook $webhook): JsonResponse
    {
        if (false === config('firefly.allow_webhooks')) {
            Log::channel('audit')->info(sprintf('User tries to view webhook #%d, but webhooks are DISABLED.', $webhook->id));

            throw new NotFoundHttpException('Webhooks are not enabled.');
        }

        Log::channel('audit')->info(sprintf('User views webhook #%d.', $webhook->id));
        $manager     = $this->getManager();

        /** @var WebhookTransformer $transformer */
        $transformer = app(WebhookTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource    = new Item($webhook, $transformer, self::RESOURCE_KEY);

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/webhooks/triggerWebhookTransaction
     *
     * This method recycles part of the code of the StoredGroupEventHandler.
     */
    public function triggerTransaction(Webhook $webhook, TransactionGroup $group): JsonResponse
    {
        if (false === config('firefly.allow_webhooks')) {
            Log::channel('audit')->info(sprintf('User tries to trigger webhook #%d on transaction group #%d, but webhooks are DISABLED.', $webhook->id, $group->id));

            throw new NotFoundHttpException('Webhooks are not enabled.');
        }

        Log::debug(sprintf('Now in triggerTransaction(%d, %d)', $webhook->id, $group->id));
        Log::channel('audit')->info(sprintf('User triggers webhook #%d on transaction group #%d.', $webhook->id, $group->id));

        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser(auth()->user());

        // tell the generator which trigger it should look for
        $engine->setTrigger($webhook->trigger);
        // tell the generator which objects to process
        $engine->setObjects(new Collection([$group]));
        // set the webhook to trigger
        $engine->setWebhooks(new Collection([$webhook]));
        // tell the generator to generate the messages
        $engine->generateMessages();

        // trigger event to send them:
        Log::debug('send event RequestedSendWebhookMessages');
        event(new RequestedSendWebhookMessages());

        return response()->json([], 204);
    }
}
