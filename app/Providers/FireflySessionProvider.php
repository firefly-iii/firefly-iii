<?php

/**
 * FireflySessionProvider.php
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

use FireflyIII\Http\Middleware\StartFireflySession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;

/**
 * Class FireflySessionProvider
 */
class FireflySessionProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    #[\Override]
    public function register(): void
    {
        $this->registerSessionManager();

        $this->registerSessionDriver();

        $this->app->singleton(StartFireflySession::class);
    }

    /**
     * Register the session manager instance.
     */
    protected function registerSessionManager(): void
    {
        $this->app->singleton(
            'session',
            static fn ($app) => new SessionManager($app)
        );
    }

    /**
     * Register the session driver instance.
     */
    protected function registerSessionDriver(): void
    {
        $this->app->singleton(
            'session.store',
            static fn ($app)
                // First, we will create the session manager which is responsible for the
                // creation of the various session drivers when they are needed by the
                // application instance, and will resolve them on a lazy load basis.
                => $app->make('session')->driver()
        );
    }
}
