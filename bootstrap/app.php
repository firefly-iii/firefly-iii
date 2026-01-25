<?php
/**
 * app.php
 * Copyright (c) 2019 james@firefly-iii.org.
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

use FireflyIII\Exceptions\Handler;
use FireflyIII\Http\Middleware\AcceptHeaders;
use FireflyIII\Http\Middleware\Authenticate;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Http\Middleware\EncryptCookies;
use FireflyIII\Http\Middleware\Installer;
use FireflyIII\Http\Middleware\InterestingMessage;
use FireflyIII\Http\Middleware\IsAdmin;
use FireflyIII\Http\Middleware\Range;
use FireflyIII\Http\Middleware\RedirectIfAuthenticated;
use FireflyIII\Http\Middleware\SecureHeaders;
use FireflyIII\Http\Middleware\StartFireflySession;
use FireflyIII\Http\Middleware\TrustProxies;
use FireflyIII\Http\Middleware\VerifyCsrfToken;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use PragmaRX\Google2FALaravel\Middleware as MFAMiddleware;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all the various parts.
|
*/

bcscale(12);

if (!function_exists('envNonEmpty')) {
    /**
     *
     * @return mixed|null
     */
    function envNonEmpty(string $key, string | int | bool | null $default = null)
    {
        $result = env($key, $default); // @phpstan-ignore-line
        if ('' === $result) {
            return $default;
        }

        return $result;
    }
}

if (!function_exists('stringIsEqual')) {
    function stringIsEqual(string $left, string $right): bool
    {
        return $left === $right;
    }
}

//$app = new Application(
//    realpath(__DIR__ . '/../')
//);


$app = Application::configure(basePath: dirname(__DIR__))
                  ->withRouting(
                      web     : __DIR__ . '/../routes/web.php',
                      commands: __DIR__ . '/../routes/console.php',
                      health  : '/up',
                  )
                  ->withMiddleware(function (Middleware $middleware): void {
                      // overrule the standard middleware
                      $middleware->use([
                                           InvokeDeferredCallbacks::class,
                                           // \Illuminate\Http\Middleware\TrustHosts::class,
                                           TrustProxies::class,
                                           HandleCors::class,
                                           PreventRequestsDuringMaintenance::class,
                                           ValidatePostSize::class,
                                           TrimStrings::class,
                                           ConvertEmptyStringsToNull::class,
                                           SecureHeaders::class,
                                       ]);

                      // overrule the web group
                      $middleware->group('web', [
                          Illuminate\Cookie\Middleware\EncryptCookies::class,
                          Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                          StartFireflySession::class,
                          Illuminate\View\Middleware\ShareErrorsFromSession::class,
                          VerifyCsrfToken::class,
                          Illuminate\Routing\Middleware\SubstituteBindings::class,
                          CreateFreshApiToken::class,
                      ]);
                      // new group?
                      $middleware->appendToGroup('binders-only', [Installer::class, EncryptCookies::class, AddQueuedCookiesToResponse::class, Binder::class]);

                      //
                      $middleware->appendToGroup('user-not-logged-in', [
                          Installer::class,
                          EncryptCookies::class,
                          AddQueuedCookiesToResponse::class,
                          StartFireflySession::class,
                          ShareErrorsFromSession::class,
                          VerifyCsrfToken::class,
                          Binder::class,
                          RedirectIfAuthenticated::class,
                      ]);

                      // more
                      $middleware->appendToGroup('user-logged-in-no-2fa', [
                          Installer::class,
                          EncryptCookies::class,
                          AddQueuedCookiesToResponse::class,
                          StartFireflySession::class,
                          ShareErrorsFromSession::class,
                          VerifyCsrfToken::class,
                          Binder::class,
                          Authenticate::class,
                      ]);

                      // simple auth
                      $middleware->appendToGroup('user-simple-auth', [
                          EncryptCookies::class,
                          AddQueuedCookiesToResponse::class,
                          StartFireflySession::class,
                          ShareErrorsFromSession::class,
                          VerifyCsrfToken::class,
                          Binder::class,
                          Authenticate::class,
                      ]);

                      // user full auth
                      $middleware->appendToGroup('user-full-auth', [
                          EncryptCookies::class,
                          AddQueuedCookiesToResponse::class,
                          StartFireflySession::class,
                          ShareErrorsFromSession::class,
                          VerifyCsrfToken::class,
                          Authenticate::class,
                          MFAMiddleware::class,
                          Range::class,
                          Binder::class,
                          InterestingMessage::class,
                          CreateFreshApiToken::class,
                      ]);

                      // admin
                      $middleware->appendToGroup('admin', [
                          EncryptCookies::class,
                          AddQueuedCookiesToResponse::class,
                          StartFireflySession::class,
                          ShareErrorsFromSession::class,
                          VerifyCsrfToken::class,
                          Authenticate::class,
                          // AuthenticateTwoFactor::class,
                          IsAdmin::class,
                          Range::class,
                          Binder::class,
                          CreateFreshApiToken::class,
                      ]);

                      // api
                      $middleware->appendToGroup('api', [AcceptHeaders::class, EnsureFrontendRequestsAreStateful::class, 'auth:api,sanctum', Binder::class]);
                      // api basic,
                      $middleware->appendToGroup('api_basic', [AcceptHeaders::class, Binder::class]);

                  })
                  ->withEvents(discover: [
                                             __DIR__ . '/../app/Listeners',
                                         ])
                  ->withExceptions(function (Exceptions $exceptions): void {
                      //
                  })->create();


//$app->withEvents(discover: [
//                           __DIR__.'/../app/Domain/Orders/Listeners',
//                       ]);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Kernel::class,
    FireflyIII\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    FireflyIII\Console\Kernel::class
);

$app->singleton(
    ExceptionHandler::class,
    Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
