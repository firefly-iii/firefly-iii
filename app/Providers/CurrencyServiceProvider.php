<?php

/**
 * CurrencyServiceProvider.php
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

use FireflyIII\Repositories\Currency\CurrencyRepository;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepository as GroupCurrencyRepository;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface as GroupCurrencyRepositoryInterface;
use FireflyIII\Repositories\UserGroups\ExchangeRate\ExchangeRateRepository;
use FireflyIII\Repositories\UserGroups\ExchangeRate\ExchangeRateRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class CurrencyServiceProvider.
 */
class CurrencyServiceProvider extends ServiceProvider
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
            CurrencyRepositoryInterface::class,
            static function (Application $app) {
                /** @var CurrencyRepository $repository */
                $repository = app(CurrencyRepository::class);
                // phpstan does not get the reference to auth
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );
        $this->app->bind(
            GroupCurrencyRepositoryInterface::class,
            static function (Application $app) {
                /** @var GroupCurrencyRepository $repository */
                $repository = app(GroupCurrencyRepository::class);
                // phpstan does not get the reference to auth
                if ($app->auth->check()) { // @phpstan-ignore-line
                    $repository->setUser(auth()->user());
                }

                return $repository;
            }
        );

        $this->app->bind(
            ExchangeRateRepositoryInterface::class,
            static function (Application $app) {
                /** @var ExchangeRateRepository */
                return app(ExchangeRateRepository::class);
            }
        );
    }
}
