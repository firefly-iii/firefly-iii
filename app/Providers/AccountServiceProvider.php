<?php
/**
 * AccountServiceProvider.php
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

use FireflyIII\Repositories\Account\AccountRepository;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTasker;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
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
    public function boot()
    {
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerTasker();
    }

    /**
     *
     */
    private function registerRepository()
    {
        $this->app->bind(
            AccountRepositoryInterface::class,
            function (Application $app) {
                /** @var AccountRepositoryInterface $repository */
                $repository = app(AccountRepository::class);

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
            AccountTaskerInterface::class,
            function (Application $app) {
                /** @var AccountTaskerInterface $tasker */
                $tasker = app(AccountTasker::class);

                if ($app->auth->check()) {
                    $tasker->setUser(auth()->user());
                }

                return $tasker;
            }
        );
    }
}
