<?php

/**
 * CategoryServiceProvider.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Providers;

use FireflyIII\Repositories\Category\CategoryRepository;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepository;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepository;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class CategoryServiceProvider.
 */
class CategoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void {}

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // phpstan does not understand reference to 'auth'.
        $this->app->bind(
            CategoryRepositoryInterface::class,
            static function (Application $app) {
                /** @var CategoryRepository $repository */
                $repository = app(CategoryRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            OperationsRepositoryInterface::class,
            static function (Application $app) {
                /** @var OperationsRepository $repository */
                $repository = app(OperationsRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            NoCategoryRepositoryInterface::class,
            static function (Application $app) {
                /** @var NoCategoryRepository $repository */
                $repository = app(NoCategoryRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
