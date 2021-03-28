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
use FireflyIII\Models\Webhook;
use FireflyIII\Repositories\Webhook\WebhookRepositoryInterface;
use FireflyIII\Transformers\WebhookTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    public const RESOURCE_KEY = 'webhooks';
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
     * Display a listing of the webhooks of the user.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $manager    = $this->getManager();
        $collection = $this->repository->all();
        $pageSize   = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $count      = $collection->count();
        $webhooks   = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($webhooks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.webhooks.index') . $this->buildParams());

        /** @var WebhookTransformer $transformer */
        $transformer = app(WebhookTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($webhooks, $transformer, self::RESOURCE_KEY);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Show single instance.
     *
     * @param Webhook $webhook
     *
     * @return JsonResponse
     */
    public function show(Webhook $webhook): JsonResponse
    {
        $manager = $this->getManager();

        /** @var WebhookTransformer $transformer */
        $transformer = app(WebhookTransformer::class);
        $transformer->setParameters($this->parameters);
        $resource = new Item($webhook, $transformer, self::RESOURCE_KEY);

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
