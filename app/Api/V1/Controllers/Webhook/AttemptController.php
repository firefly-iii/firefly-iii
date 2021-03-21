<?php
/*
 * AttemptController.php
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

namespace FireflyIII\Api\V1\Controllers\Webhook;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Transformers\WebhookAttemptTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class AttemptController
 */
class AttemptController extends Controller
{
    public const RESOURCE_KEY = 'webhook_attempts';
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
     * @param Webhook $webhook
     *
     * @return JsonResponse
     */
    public function index(Webhook $webhook, WebhookMessage $message): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('Webhook and webhook message are no match');
        }

        $manager    = $this->getManager();
        $pageSize   = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $collection = $this->repository->getAttempts($message);
        $count      = $collection->count();
        $attempts   = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($attempts, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.webhooks.attempts.index', [$webhook->id, $message->id]) . $this->buildParams());

        /** @var WebhookAttemptTransformer $transformer */
        $transformer = app(WebhookAttemptTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($attempts, $transformer, 'webhook_attempts');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Show single instance.
     *
     * @param Webhook        $webhook
     * @param WebhookMessage $message
     * @param WebhookAttempt $attempt
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function show(Webhook $webhook, WebhookMessage $message, WebhookAttempt $attempt): JsonResponse
    {
        if ($message->webhook_id !== $webhook->id) {
            throw new FireflyException('Webhook and webhook message are no match');
        }
        if ($attempt->webhook_message_id !== $message->id) {
            throw new FireflyException('Webhook message and webhook attempt are no match');

        }

        $manager = $this->getManager();

        /** @var WebhookAttemptTransformer $transformer */
        $transformer = app(WebhookAttemptTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new Item($attempt, $transformer, self::RESOURCE_KEY);

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}