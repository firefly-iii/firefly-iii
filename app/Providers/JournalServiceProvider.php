<?php
/**
 * JournalServiceProvider.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Providers;

use FireflyIII\Helpers\Collector\GroupCollector;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Helpers\Collector\GroupSumCollector;
use FireflyIII\Helpers\Collector\GroupSumCollectorInterface;
use FireflyIII\Helpers\Collector\TransactionCollector;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Repositories\Journal\JournalRepository;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepository;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * @codeCoverageIgnore
 * Class JournalServiceProvider.
 */
class JournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->registerRepository();
        $this->registerGroupRepository();
        $this->registerCollector();
        $this->registerGroupCollector();
        $this->registerSumCollector();
    }

    /**
     * Register the collector.
     */
    private function registerCollector(): void
    {
        $this->app->bind(
            TransactionCollectorInterface::class,
            function (Application $app) {
                /** @var TransactionCollectorInterface $collector */
                $collector = app(TransactionCollector::class);
                if ($app->auth->check()) {
                    $collector->setUser(auth()->user());
                }

                return $collector;
            }
        );
    }

    /**
     *
     */
    private function registerGroupCollector(): void
    {
        $this->app->bind(
            GroupCollectorInterface::class,
            function (Application $app) {
                /** @var GroupCollectorInterface $collector */
                $collector = app(GroupCollector::class);
                if ($app->auth->check()) {
                    $collector->setUser(auth()->user());
                }

                return $collector;
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
            function (Application $app) {
                /** @var TransactionGroupRepositoryInterface $repository */
                $repository = app(TransactionGroupRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    /**
     * Register repository.
     */
    private function registerRepository(): void
    {
        $this->app->bind(
            JournalRepositoryInterface::class,
            function (Application $app) {
                /** @var JournalRepositoryInterface $repository */
                $repository = app(JournalRepository::class);
                if ($app->auth->check()) {
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
    }

    /**
     * Register sum collector.
     */
    private function registerSumCollector(): void
    {
        $this->app->bind(
            GroupSumCollectorInterface::class,
            function (Application $app) {
                /** @var GroupSumCollector $collector */
                $collector = app(GroupSumCollector::class);
                if ($app->auth->check()) {
                    $collector->setUser(auth()->user());
                }

                return $collector;
            }
        );
    }
}
