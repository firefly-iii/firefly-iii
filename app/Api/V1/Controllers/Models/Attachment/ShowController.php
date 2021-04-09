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

namespace FireflyIII\Api\V1\Controllers\Models\Attachment;

use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Middleware\ApiDemoUser;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;

/**
 * Class ShowController
 */
class ShowController extends Controller
{
    private AttachmentRepositoryInterface $repository;

    /**
     * ShowController constructor.
     *
     * @codeCoverageIgnore
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
     * Download an attachment.
     *
     * @param Attachment $attachment
     *
     * @codeCoverageIgnore
     * @return LaravelResponse
     * @throws   FireflyException
     */
    public function download(Attachment $attachment): LaravelResponse
    {
        if (false === $attachment->uploaded) {
            throw new FireflyException('200000: File has not been uploaded (yet).');
        }
        if (0 === $attachment->size) {
            throw new FireflyException('200000: File has not been uploaded (yet).');
        }
        if ($this->repository->exists($attachment)) {
            $content = $this->repository->getContent($attachment);
            if ('' === $content) {
                throw new FireflyException('200002: File is empty (zero bytes).');
            }
            $quoted = sprintf('"%s"', addcslashes(basename($attachment->filename), '"\\'));

            /** @var LaravelResponse $response */
            $response = response($content);
            $response
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename=' . $quoted)
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Connection', 'Keep-Alive')
                ->header('Expires', '0')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public')
                ->header('Content-Length', (string)strlen($content));

            return $response;
        }
        throw new FireflyException('200003: File does not exist.');
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        $manager = $this->getManager();

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of attachments. Count it and split it.
        $collection  = $this->repository->get();
        $count       = $collection->count();
        $attachments = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($attachments, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.attachments.index') . $this->buildParams());

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($attachments, $transformer, 'attachments');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }

    /**
     * Display the specified resource.
     *
     * @param Attachment $attachment
     *
     * @return JsonResponse
     */
    public function show(Attachment $attachment): JsonResponse
    {
        $manager = $this->getManager();
        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($attachment, $transformer, 'attachments');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);
    }
}
