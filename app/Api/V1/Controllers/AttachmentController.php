<?php
/**
 * AttachmentController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\AttachmentStoreRequest;
use FireflyIII\Api\V1\Requests\AttachmentUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use function strlen;

/**
 * Class AttachmentController.
 *
 *
 */
class AttachmentController extends Controller
{
    /** @var AttachmentRepositoryInterface The attachment repository */
    private $repository;

    /**
     * AccountController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
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
     * Remove the specified resource from storage.
     * @codeCoverageIgnore
     * @param Attachment $attachment
     *
     * @return JsonResponse
     */
    public function delete(Attachment $attachment): JsonResponse
    {
        $this->repository->destroy($attachment);

        return response()->json([], 204);
    }

    /**
     * Download an attachment.
     *
     * @param Attachment $attachment
     * @codeCoverageIgnore
     * @return LaravelResponse
     * @throws   FireflyException
     */
    public function download(Attachment $attachment): LaravelResponse
    {
        if (false === $attachment->uploaded) {
            throw new FireflyException('No file has been uploaded for this attachment (yet).');
        }
        if ($this->repository->exists($attachment)) {
            $content = $this->repository->getContent($attachment);
            $quoted  = sprintf('"%s"', addcslashes(basename($attachment->filename), '"\\'));

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
                ->header('Content-Length', strlen($content));

            return $response;
        }
        throw new FireflyException('Could not find the indicated attachment. The file is no longer there.');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @codeCoverageIgnore
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of accounts. Count it and split it.
        $collection  = $this->repository->get();
        $count       = $collection->count();
        $attachments = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($attachments, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.attachments.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($attachments, $transformer, 'attachments');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Attachment $attachment
     * @return JsonResponse
     */
    public function show(Request $request, Attachment $attachment): JsonResponse
    {
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($attachment, $transformer, 'attachments');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AttachmentStoreRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(AttachmentStoreRequest $request): JsonResponse
    {
        $data       = $request->getAll();
        $attachment = $this->repository->store($data);
        $manager    = new Manager;
        $baseUrl    = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($attachment, $transformer, 'attachments');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AttachmentUpdateRequest $request
     * @param Attachment $attachment
     *
     * @return JsonResponse
     */
    public function update(AttachmentUpdateRequest $request, Attachment $attachment): JsonResponse
    {
        $data = $request->getAll();
        $this->repository->update($attachment, $data);
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        /** @var AttachmentTransformer $transformer */
        $transformer = app(AttachmentTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($attachment, $transformer, 'attachments');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Upload an attachment.
     * @codeCoverageIgnore
     * @param Request $request
     * @param Attachment $attachment
     *
     * @return JsonResponse
     */
    public function upload(Request $request, Attachment $attachment): JsonResponse
    {
        /** @var AttachmentHelperInterface $helper */
        $helper = app(AttachmentHelperInterface::class);
        $body   = $request->getContent();
        $helper->saveAttachmentFromApi($attachment, $body);

        return response()->json([], 204);
    }

}
