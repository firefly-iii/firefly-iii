<?php

/**
 * JournalServiceProvider.php
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

use FireflyIII\Helpers\Collector\GroupCollector;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Repositories\Journal\JournalAPIRepository;
use FireflyIII\Repositories\Journal\JournalAPIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalCLIRepository;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepository;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepository;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Class JournalServiceProvider.
 */
class JournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void {}

    /**
     * Register the application services.
     */
    #[Override]
    public function register(): void
    {
        $this->registerRepository();
        $this->registerGroupRepository();
        $this->registerGroupCollector();
    }

    /**
     * Register repository.
     */
    private function registerRepository(): void
    {
        $this->app->bind(
            JournalRepositoryInterface::class,
            static function (Application $app) {
                /** @var JournalRepositoryInterface $repository */
                $repository = app(JournalRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // also bind new API repository
        $this->app->bind(
            JournalAPIRepositoryInterface::class,
            static function (Application $app) {
                /** @var JournalAPIRepositoryInterface $repository */
                $repository = app(JournalAPIRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        // also bind new CLI repository
        $this->app->bind(
            JournalCLIRepositoryInterface::class,
            static function (Application $app) {
                /** @var JournalCLIRepositoryInterface $repository */
                $repository = app(JournalCLIRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    /**
     * Register group repos.
     */
    private function registerGroupRepository(): void
    {
        $this->app->bind(
            TransactionGroupRepositoryInterface::class,
            static function (Application $app) {
                /** @var TransactionGroupRepositoryInterface $repository */
                $repository = app(TransactionGroupRepository::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    private function registerGroupCollector(): void
    {
        $this->app->bind(
            GroupCollectorInterface::class,
            static function (Application $app) {
                /** @var GroupCollectorInterface $collector */
                $collector = app(GroupCollector::class);
                if ($app->auth->check()) { // @phpstan-ignore-line (phpstan does not understand the reference to auth)
                    $collector->setUser(auth()->user());
                }

                return $collector;
            }
        );
    }
}
