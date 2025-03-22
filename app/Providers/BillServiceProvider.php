<?php

/**
 * BillServiceProvider.php
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

use FireflyIII\Repositories\Bill\BillRepository;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class BillServiceProvider.
 */
class BillServiceProvider extends ServiceProvider
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
        $this->app->bind(
            BillRepositoryInterface::class,
            static function (Application $app) {
                /** @var BillRepositoryInterface $repository */
                $repository = app(BillRepository::class);

                // reference to auth is not understood by phpstan.
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

    }
}
