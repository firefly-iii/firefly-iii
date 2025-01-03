<?php

/**
 * BudgetServiceProvider.php
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

use FireflyIII\Repositories\Budget\AvailableBudgetRepository;
use FireflyIII\Repositories\Budget\AvailableBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetLimitRepository;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepository;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\NoBudgetRepository;
use FireflyIII\Repositories\Budget\NoBudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepository;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Budget\AvailableBudgetRepository as AdminAbRepository;
use FireflyIII\Repositories\UserGroups\Budget\AvailableBudgetRepositoryInterface as AdminAbRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Budget\BudgetRepository as AdminBudgetRepository;
use FireflyIII\Repositories\UserGroups\Budget\BudgetRepositoryInterface as AdminBudgetRepositoryInterface;
use FireflyIII\Repositories\UserGroups\Budget\OperationsRepository as AdminOperationsRepository;
use FireflyIII\Repositories\UserGroups\Budget\OperationsRepositoryInterface as AdminOperationsRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class BudgetServiceProvider.
 */
class BudgetServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void {}

    /**
     * Register the application services.
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    public function register(): void
    {
        // reference to auth is not understood by phpstan.
        $this->app->bind(
            BudgetRepositoryInterface::class,
            static function (Application $app) {
                /** @var BudgetRepositoryInterface $repository */
                $repository = app(BudgetRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            AdminBudgetRepositoryInterface::class,
            static function (Application $app) {
                /** @var AdminBudgetRepositoryInterface $repository */
                $repository = app(AdminBudgetRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // available budget repos
        $this->app->bind(
            AvailableBudgetRepositoryInterface::class,
            static function (Application $app) {
                /** @var AvailableBudgetRepositoryInterface $repository */
                $repository = app(AvailableBudgetRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // available budget repos
        $this->app->bind(
            AdminAbRepositoryInterface::class,
            static function (Application $app) {
                /** @var AdminAbRepositoryInterface $repository */
                $repository = app(AdminAbRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // budget limit repository.
        $this->app->bind(
            BudgetLimitRepositoryInterface::class,
            static function (Application $app) {
                /** @var BudgetLimitRepositoryInterface $repository */
                $repository = app(BudgetLimitRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // no budget repos
        $this->app->bind(
            NoBudgetRepositoryInterface::class,
            static function (Application $app) {
                /** @var NoBudgetRepositoryInterface $repository */
                $repository = app(NoBudgetRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // operations repos
        $this->app->bind(
            OperationsRepositoryInterface::class,
            static function (Application $app) {
                /** @var OperationsRepositoryInterface $repository */
                $repository = app(OperationsRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
        $this->app->bind(
            AdminOperationsRepositoryInterface::class,
            static function (Application $app) {
                /** @var AdminOperationsRepositoryInterface $repository */
                $repository = app(AdminOperationsRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }
}
