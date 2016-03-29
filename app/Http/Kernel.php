<?php
declare(strict_types = 1);

namespace FireflyIII\Http;

use FireflyIII\Http\Middleware\Authenticate;
use FireflyIII\Http\Middleware\AuthenticateTwoFactor;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Http\Middleware\EncryptCookies;
use FireflyIII\Http\Middleware\IsConfirmed;
use FireflyIII\Http\Middleware\IsNotConfirmed;
use FireflyIII\Http\Middleware\Range;
use FireflyIII\Http\Middleware\RedirectIfAuthenticated;
use FireflyIII\Http\Middleware\RedirectIfTwoFactorAuthenticated;
use FireflyIII\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Class Kernel
 *
 * @package FireflyIII\Http
 */
class Kernel extends HttpKernel
{
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
            'web'                                => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
            ],
            // MUST NOT be logged in. Does not care about 2FA or confirmation.
            'user-not-logged-in'                 => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                RedirectIfAuthenticated::class,
            ],

            // MUST be logged in.
            // MUST NOT have 2FA
            // don't care about confirmation:
            'user-logged-in-no-2fa'              => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                Authenticate::class,
                RedirectIfTwoFactorAuthenticated::class,
            ],
            // MUST be logged in
            // MUST have 2FA
            // MUST NOT have confirmation.
            'user-logged-in-2fa-no-activation'   => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                Authenticate::class,
                AuthenticateTwoFactor::class,
                IsNotConfirmed::class,
            ],
            // MUST be logged in
            // MUST have 2fa
            // MUST be confirmed.
            // (this group includes the other Firefly middleware)
            'user-full-auth' => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                Authenticate::class,
                AuthenticateTwoFactor::class,
                IsConfirmed::class,
                Range::class,
                Binder::class,
            ],

//
//            // must be authenticated
//            // must be 2fa (if enabled)
//            // must be activated account
//            'web-auth'                           => [
//                EncryptCookies::class,
//                AddQueuedCookiesToResponse::class,
//                StartSession::class,
//                ShareErrorsFromSession::class,
//                VerifyCsrfToken::class,
//                Authenticate::class,
//                AuthenticateTwoFactor::class,
//                IsConfirmed::class,
//            ],
//            // must be authenticated
//            // must be 2fa (if enabled)
//            // must NOT be activated account
//            'web-auth-no-confirm'                => [
//                EncryptCookies::class,
//                AddQueuedCookiesToResponse::class,
//                StartSession::class,
//                ShareErrorsFromSession::class,
//                VerifyCsrfToken::class,
//                Authenticate::class,
//                AuthenticateTwoFactor::class,
//                IsNotConfirmed::class,
//            ],
//            // must be authenticated
//            // does not care about 2fa
//            // must be confirmed.
//            'web-auth-no-two-factor'             => [
//                EncryptCookies::class,
//                AddQueuedCookiesToResponse::class,
//                StartSession::class,
//                ShareErrorsFromSession::class,
//                VerifyCsrfToken::class,
//                Authenticate::class,
//                RedirectIfTwoFactorAuthenticated::class,
//                IsConfirmed::class,
//            ],
//            'web-auth-no-two-factor-any-confirm' => [
//                EncryptCookies::class,
//                AddQueuedCookiesToResponse::class,
//                StartSession::class,
//                ShareErrorsFromSession::class,
//                VerifyCsrfToken::class,
//                Authenticate::class,
//                RedirectIfTwoFactorAuthenticated::class,
//            ],
//            'web-auth-range'                     => [
//                EncryptCookies::class,
//                AddQueuedCookiesToResponse::class,
//                StartSession::class,
//                ShareErrorsFromSession::class,
//                VerifyCsrfToken::class,
//                Authenticate::class,
//                AuthenticateTwoFactor::class,
//                IsConfirmed::class,
//                Range::class,
//                Binder::class,
//            ],

            'api' => [
                'throttle:60,1',
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
            'guest'      => RedirectIfAuthenticated::class,
            'throttle'   => ThrottleRequests::class,
            'range'      => Range::class,
        ];
}
