<?php

/*
 * CategoryController.php
 * Copyright (c) 2024 james@firefly-iii.org
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

namespace FireflyIII\Api\V2\Controllers\Autocomplete;

use FireflyIII\Api\V2\Controllers\Controller;
use FireflyIII\Api\V2\Request\Autocomplete\AutocompleteRequest;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * Class TagController
 */
class TagController extends Controller
{
    private TagRepositoryInterface $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(TagRepositoryInterface::class);
                $this->repository->setUserGroup($this->validateUserGroup($request));

                return $next($request);
            }
        );
    }

    /**
     * Documentation: https://api-docs.firefly-iii.org/?urls.primaryName=2.1.0%20(v2)#/autocomplete/getTagsAC
     */
    public function tags(AutocompleteRequest $request): JsonResponse
    {
        $queryParameters = $request->getParameters();
        $result          = $this->repository->searchTag($queryParameters['query'], $queryParameters['size']);
        $filtered        = $result->map(
            static function (Tag $item) {
                return [
                    'id'    => (string) $item->id,
                    'title' => $item->tag,
                    'value' => (string) $item->id,
                    'label' => $item->tag,
                    'meta'  => [],
                ];
            }
        );

        return response()->json($filtered);
    }
}
