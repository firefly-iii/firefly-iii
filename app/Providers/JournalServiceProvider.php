<?php
/**
 * JournalServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);


namespace FireflyIII\Providers;

use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Repositories\Journal\JournalRepository;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalTasker;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use FireflyIII\Repositories\Journal\JournalUpdate;
use FireflyIII\Repositories\Journal\JournalUpdateInterface;
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
        $this->registerUpdater();
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

    /**
     *
     */
    private function registerUpdater()
    {
        $this->app->bind(
            JournalUpdateInterface::class,
            function (Application $app) {
                /** @var JournalUpdateInterface $tasker */
                $update = app(JournalUpdate::class);

                if ($app->auth->check()) {
                    $update->setUser(auth()->user());
                }

                return $update;
            }
        );
    }
}
