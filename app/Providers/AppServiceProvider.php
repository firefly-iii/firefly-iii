<?php

/**
 * AppServiceProvider.php
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

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

/**
 * Class AppServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Response::macro('api', function (array $value) {
            $headers = [
                'Cache-Control' => 'no-store',
            ];
            $uuid    = (string) request()->header('X-Trace-Id');
            if ('' !== trim($uuid) && (1 === \Safe\preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', trim($uuid)))) {
                $headers['X-Trace-Id'] = $uuid;
            }

            return response()
                ->json($value)
                ->withHeaders($headers)
            ;
        });

        // blade extension
        Blade::directive('activeXRoutePartial', function (string $route) {
            $name = \Route::getCurrentRoute()->getName() ?? '';
            if (str_contains($name, $route)) {
                return 'menu-open';
            }

            return '';
        });
        Blade::if('partialroute', function (string $route, string $firstParam = '') {
            $name       = Route::getCurrentRoute()->getName() ?? '';
            if ('' === $firstParam && str_contains($name, $route)) {
                return true;
            }

            /** @var null|array $params */
            $params     = Route::getCurrentRoute()->parameters();
            $params ??= [];
            $objectType = $params['objectType'] ?? '';
            if ($objectType === $firstParam && str_contains($name, $route)) {
                return true;
            }

            return false;
        });
    }

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        Passport::ignoreRoutes();
        //        Passport::ignoreMigrations();
        //        Sanctum::ignoreMigrations();
    }
}
