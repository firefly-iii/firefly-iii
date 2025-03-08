<?php

/**
 * AccountServiceProvider.php
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

use FireflyIII\Repositories\Account\AccountRepository;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTasker;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Account\OperationsRepository;
use FireflyIII\Repositories\Account\OperationsRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class AccountServiceProvider.
 */
class AccountServiceProvider extends ServiceProvider
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
        $this->registerRepository();
        $this->registerTasker();
    }

    /**
     * Register account repository
     */
    private function registerRepository(): void
    {
        $this->app->bind(
            AccountRepositoryInterface::class,
            static function (Application $app) {
                /** @var AccountRepositoryInterface $repository */
                $repository = app(AccountRepository::class);

                // phpstan thinks auth does not exist.
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

                // phpstan thinks auth does not exist.
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    /**
     * Register the tasker.
     */
    private function registerTasker(): void
    {
        $this->app->bind(
            AccountTaskerInterface::class,
            static function (Application $app) {
                /** @var AccountTaskerInterface $tasker */
                $tasker = app(AccountTasker::class);

                // phpstan thinks auth does not exist.
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $tasker->setUser(auth()->user());
                }

                return $tasker;
            }
        );
    }
}
