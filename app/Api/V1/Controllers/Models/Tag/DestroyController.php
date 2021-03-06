<?php
/*
 * DestroyController.php
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

namespace FireflyIII\Api\V1\Controllers\Models\Tag;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\Http\Api\TransactionFilter;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class DestroyController
 */
class DestroyController extends Controller
{
    private TagRepositoryInterface $repository;


    /**
     * TagController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                $this->repository = app(TagRepositoryInterface::class);
                $this->repository->setUser($user);

                return $next($request);
            }
        );
    }



    /**
     * Delete the resource.
     *
     * @param Tag $tag
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->repository->destroy($tag);

        return response()->json([], 204);
    }
}