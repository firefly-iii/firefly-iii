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

namespace FireflyIII\Api\V1\Controllers\Models\Category;


use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Models\Category\UpdateRequest;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Transformers\CategoryTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item;

/**
 * Class UpdateController
 */
class UpdateController extends Controller
{
    private CategoryRepositoryInterface $repository;

    /**
     * CategoryController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $admin */
                $admin = auth()->user();

                /** @var CategoryRepositoryInterface repository */
                $this->repository = app(CategoryRepositoryInterface::class);
                $this->repository->setUser($admin);

                return $next($request);
            }
        );
    }

    /**
     * Update the category.
     *
     * @param UpdateRequest $request
     * @param Category              $category
     *
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, Category $category): JsonResponse
    {
        $data     = $request->getAll();
        $category = $this->repository->update($category, $data);
        $manager  = $this->getManager();

        /** @var CategoryTransformer $transformer */
        $transformer = app(CategoryTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($category, $transformer, 'categories');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', self::CONTENT_TYPE);

    }
}