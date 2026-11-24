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

use FireflyIII\Support\Authentication\RemoteUserGuard;
use FireflyIII\Support\Authentication\RemoteUserProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Override;
use RuntimeException;

use function Safe\base64_decode;
use function Safe\preg_match;

/**
 * Class AppServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    private const array PUBLIC_APP_KEYS = ['SomeRandomStringOf32CharsExactly'];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->refusePublicAppKey();
        // do not check permissions for key files.
        Passport::$validateKeyPermissions = false;

        Schema::defaultStringLength(191);

        Response::macro('api', function (array $value) {
            $headers = ['Cache-Control' => 'no-store'];
            $uuid    = (string) request()->header('X-Trace-Id');
            if ('' !== trim($uuid) && 1 === preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', trim($uuid))) {
                $headers['X-Trace-Id'] = $uuid;
            }

            return response()->json($value)->withHeaders($headers);
        });

        Auth::extend('remote_user_guard', function (Application $app, string $name, array $config): RemoteUserGuard {
            return new RemoteUserGuard(Auth::createUserProvider($config['provider']));
        });

        // new code for authorization.
        Passport::authorizationView('auth.oauth.authorize');

        Auth::provider('remote_user_provider', function (Application $app, array $config): RemoteUserProvider {
            return new RemoteUserProvider();
        });
    }

    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        // Passport::ignoreRoutes();
        //        Passport::ignoreMigrations();
        //        Sanctum::ignoreMigrations();
    }

    private function refusePublicAppKey(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7), true);
        }
        if (in_array($key, self::PUBLIC_APP_KEYS, true)) {
            throw new RuntimeException(
                'APP_KEY is the example value from .env.example. Set APP_KEY to a random string of exactly 32 characters, for example the output of: head /dev/urandom | LC_ALL=C tr -dc \'A-Za-z0-9\' | head -c 32'
            );
        }
    }
}
