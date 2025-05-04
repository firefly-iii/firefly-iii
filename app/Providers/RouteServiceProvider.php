<?php

/**
 * RouteServiceProvider.php
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

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Class RouteServiceProvider
 */
class RouteServiceProvider extends ServiceProvider
{
    public const string   HOME = '/';
    protected $namespace       = '';

    /**
     * Define the routes for the application.
     */
    #[\Override]
    public function boot(): void
    {
        $this->routes(function (): void {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'))
            ;

            Route::prefix('api/v1/cron')
                ->middleware('api_basic')
                ->namespace($this->namespace)
                ->group(base_path('routes/api-noauth.php'))
            ;

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'))
            ;
        });
    }
}
