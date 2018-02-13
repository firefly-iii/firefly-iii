<?php
/**
 * UserController.php
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

use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\UserTransformer;
use FireflyIII\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;
use Preferences;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

/**
 * Class UserController
 */
class UserController extends Controller
{

    /** @var UserRepositoryInterface */
    private $repository;

    /**
     * UserController constructor.
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var UserRepositoryInterface repository */
                $this->repository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \FireflyIII\User $user
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(User $user)
    {
        if (auth()->user()->hasRole('owner')) {
            $this->repository->destroy($user);

            return response()->json([], 204);
        }
        throw new AccessDeniedException('');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $pageSize   = intval(Preferences::getForUser(auth()->user(), 'listPageSize', 50)->data);
        $collection = $this->repository->all();
        $count      = $collection->count();
        $users      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($users, $count, $pageSize, $this->parameters->get('page'));
        $manager   = new Manager();
        $baseUrl   = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new FractalCollection($users, new UserTransformer($this->parameters), 'users');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray());
    }

    /**
     * @param Request $request
     * @param User    $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, User $user)
    {

        $manager = new Manager();
        //$manager->parseIncludes(['attachments', 'journals', 'user']);
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($user, new UserTransformer($this->parameters), 'users');

        return response()->json($manager->createData($resource)->toArray());
    }

    /**
     * @param AccountRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AccountRequest $request)
    {

    }

    /**
     * @param AccountRequest $request
     * @param Account        $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AccountRequest $request, Account $account)
    {


    }

}