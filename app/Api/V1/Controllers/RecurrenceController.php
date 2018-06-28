<?php
/**
 * RecurrenceController.php
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

use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class RecurrenceController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                // todo add local repositories.
                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param string $object
     *
     * @return JsonResponse
     */
    public function delete(string $object): JsonResponse
    {
        // todo delete object.

        return response()->json([], 204);
    }

    /**
     * List all of them.
     *
     * @param Request $request
     *
     * @return JsonResponse]
     */
    public function index(Request $request): JsonResponse
    {
        // todo implement.

    }

    /**
     * List single resource.
     *
     * @param Request $request
     * @param string  $object
     *
     * @return JsonResponse
     */
    public function show(Request $request, string $object): JsonResponse
    {
        // todo implement me.

    }

    /**
     * Store new object.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // todo replace code and replace request object.

    }

    /**
     * @param Request $request
     * @param string  $object
     *
     * @return JsonResponse
     */
    public function update(Request $request, string $object): JsonResponse
    {
        // todo replace code and replace request object.

    }
}