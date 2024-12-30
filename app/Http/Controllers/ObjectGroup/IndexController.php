<?php

/**
 * IndexController.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers\ObjectGroup;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Repositories\ObjectGroup\ObjectGroupRepositoryInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    private ObjectGroupRepositoryInterface $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-envelope-o');
                app('view')->share('title', (string) trans('firefly.object_groups_page_title'));
                $this->repository = app(ObjectGroupRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @return Factory|View
     */
    public function index()
    {
        $this->repository->deleteEmpty();
        $this->repository->resetOrder();
        $subTitle     = (string) trans('firefly.object_groups_index');
        $objectGroups = $this->repository->get();

        return view('object-groups.index', compact('subTitle', 'objectGroups'));
    }

    /**
     * @return JsonResponse
     */
    public function setOrder(Request $request, ObjectGroup $objectGroup)
    {
        app('log')->debug(sprintf('Found object group #%d "%s"', $objectGroup->id, $objectGroup->title));
        $newOrder = (int) $request->get('order');
        $this->repository->setOrder($objectGroup, $newOrder);

        return response()->json([]);
    }
}
