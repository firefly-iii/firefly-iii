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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Repositories\Journal\JournalRepository;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTasker;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class JournalServiceProvider
 *
 * @package FireflyIII\Providers
 */
class JournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerTasker();
        $this->registerCollector();
    }

    /**
     *
     */
    private function registerCollector()
    {
        $this->app->bind(
            JournalCollectorInterface::class,
            function (Application $app) {
                /** @var JournalCollectorInterface $collector */
                $collector = app(JournalCollector::class);
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
    private function registerRepository()
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
     *
     */
    private function registerTasker()
    {
        $this->app->bind(
            JournalTaskerInterface::class,
            function (Application $app) {
                /** @var JournalTaskerInterface $tasker */
                $tasker = app(JournalTasker::class);

                if ($app->auth->check()) {
                    $tasker->setUser(auth()->user());
                }

                return $tasker;
            }
        );
    }
}
