<?php
/**
 * Kernel.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

namespace FireflyIII\Http;

use FireflyIII\Http\Middleware\Authenticate;
use FireflyIII\Http\Middleware\AuthenticateTwoFactor;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Http\Middleware\EncryptCookies;
use FireflyIII\Http\Middleware\IsAdmin;
use FireflyIII\Http\Middleware\Range;
use FireflyIII\Http\Middleware\RedirectIfAuthenticated;
use FireflyIII\Http\Middleware\RedirectIfTwoFactorAuthenticated;
use FireflyIII\Http\Middleware\Sandstorm;
use FireflyIII\Http\Middleware\StartFireflySession;
use FireflyIII\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Class Kernel
 *
 * @package FireflyIII\Http
 */
class Kernel extends HttpKernel
{

    /**
     * The bootstrap classes for the application.
     *
     * Next upgrade verify these are the same.
     *
     * @var array
     */
    protected $bootstrappers
        = [
            'Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables',
            'Illuminate\Foundation\Bootstrap\LoadConfiguration',
            'Illuminate\Foundation\Bootstrap\HandleExceptions',
            'Illuminate\Foundation\Bootstrap\RegisterFacades',
            'Illuminate\Foundation\Bootstrap\RegisterProviders',
            'Illuminate\Foundation\Bootstrap\BootProviders',
        ];

    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware
        = [
            CheckForMaintenanceMode::class,
        ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups
        = [
            // does not check login
            // does not check 2fa
            // does not check activation
            'web'                   => [
                Sandstorm::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ],


            // MUST NOT be logged in. Does not care about 2FA or confirmation.
            'user-not-logged-in'    => [
                Sandstorm::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                RedirectIfAuthenticated::class,
            ],
            // MUST be logged in.
            // MUST NOT have 2FA
            // don't care about confirmation:
            'user-logged-in-no-2fa' => [
                Sandstorm::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                Authenticate::class,
                RedirectIfTwoFactorAuthenticated::class,
            ],

            // MUST be logged in
            // don't care about 2fa
            // don't care about confirmation.
            'user-simple-auth'      => [
                Sandstorm::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                Authenticate::class,
            ],

            // MUST be logged in
            // MUST have 2fa
            // MUST be confirmed.
            // (this group includes the other Firefly middleware)
            'user-full-auth'        => [
                Sandstorm::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                Authenticate::class,
                AuthenticateTwoFactor::class,
                Range::class,
                Binder::class,
            ],
            // MUST be logged in
            // MUST have 2fa
            // MUST be confirmed.
            // MUST have owner role
            // (this group includes the other Firefly middleware)
            'admin'                 => [
                Sandstorm::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                Authenticate::class,
                AuthenticateTwoFactor::class,
                IsAdmin::class,
                Range::class,
                Binder::class,
            ],


            'api' => [
                'throttle:60,1',
                'bindings',
            ],
        ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware
        = [
            'auth'       => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'bindings'   => SubstituteBindings::class,
            'can'        => Authorize::class,
            'guest'      => RedirectIfAuthenticated::class,
            'throttle'   => ThrottleRequests::class,
            'range'      => Range::class,
        ];
}
