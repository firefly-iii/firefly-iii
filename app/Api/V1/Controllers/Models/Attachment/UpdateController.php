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

namespace FireflyIII\Api\V1\Controllers\Models\Attachment;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Middleware\ApiDemoUser;
use FireflyIII\Api\V1\Requests\Models\Attachment\UpdateRequest;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use League\Fractal\Resource\Item;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class UpdateController
 */
class UpdateController extends Controller
{
    private AttachmentRepositoryInterface $repository;

    /**
     * UpdateController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(ApiDemoUser::class)->except(['delete', 'download', 'show', 'index']);
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(AttachmentRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/?urls.primaryName=2.0.0%20(v1)#/attachments/updateAttachment
     *
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Attachment $attachment): JsonResponse
    {
        if (true === auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('Demo user tries to access attachment API in %s', __METHOD__));

            throw new NotFoundHttpException();
        }
        $data        = $request->getAll();
        $this->repository->update($attachment, $data);
        $manager     = $this->getManager();

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource    = new Item($attachment, $transformer, 'attachments');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
