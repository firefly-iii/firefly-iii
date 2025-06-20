<?php

/**
 * Kernel.php
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

namespace FireflyIII\Http;

use FireflyIII\Http\Middleware\AcceptHeaders;
use FireflyIII\Http\Middleware\Authenticate;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Http\Middleware\EncryptCookies;
use FireflyIII\Http\Middleware\InstallationId;
use FireflyIII\Http\Middleware\Installer;
use FireflyIII\Http\Middleware\InterestingMessage;
use FireflyIII\Http\Middleware\IsAdmin;
use FireflyIII\Http\Middleware\Range;
use FireflyIII\Http\Middleware\RedirectIfAuthenticated;
use FireflyIII\Http\Middleware\SecureHeaders;
use FireflyIII\Http\Middleware\StartFireflySession;
use FireflyIII\Http\Middleware\TrimStrings;
use FireflyIII\Http\Middleware\TrustProxies;
use FireflyIII\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use PragmaRX\Google2FALaravel\Middleware as MFAMiddleware;

/**
 * Class Kernel
 */
class Kernel extends HttpKernel
{
    protected $middleware
        = [
            SecureHeaders::class,
            CheckForMaintenanceMode::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
            TrustProxies::class,
            InstallationId::class,
        ];
    protected $middlewareAliases
        = [
            'auth'       => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'bindings'   => Binder::class,
            'can'        => Authorize::class,
            'guest'      => RedirectIfAuthenticated::class,
            'throttle'   => ThrottleRequests::class,
        ];
    protected $middlewareGroups
        = [
            // does not check login
            // does not check 2fa
            // does not check activation
            'web'                   => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                AuthenticateSession::class,
                CreateFreshApiToken::class,
            ],

            // only the basic variable binders.
            'binders-only'          => [
                Installer::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                Binder::class,
            ],

            // MUST NOT be logged in. Does not care about 2FA or confirmation.
            'user-not-logged-in'    => [
                Installer::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                Binder::class,
                RedirectIfAuthenticated::class,
            ],
            // MUST be logged in.
            // MUST NOT have 2FA
            // don't care about confirmation:
            'user-logged-in-no-2fa' => [
                Installer::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                Binder::class,
                Authenticate::class,
                // RedirectIfTwoFactorAuthenticated::class,
            ],

            // MUST be logged in
            // don't care about 2fa
            // don't care about confirmation.
            'user-simple-auth'      => [
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartFireflySession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                Binder::class,
                Authenticate::class,
            ],

            // MUST be logged in
            // MUST have 2fa
            // MUST be confirmed.
            // (this group includes the other Firefly III middleware)
            'user-full-auth'        => [
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
            ],
            // MUST be logged in
            // MUST have 2fa
            // MUST be confirmed.
            // MUST have owner role
            // (this group includes the other Firefly III middleware)
            'admin'                 => [
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
            ],

            // full API authentication
            'api'                   => [
                AcceptHeaders::class,
                EnsureFrontendRequestsAreStateful::class,
                'auth:api',
                'bindings',
            ],
            // do only bindings, no auth
            'api_basic'             => [
                AcceptHeaders::class,
                'bindings',
            ],
        ];
    protected $middlewarePriority
        = [
            StartFireflySession::class,
            ShareErrorsFromSession::class,
            Authenticate::class,
            Binder::class,
            Authorize::class,
        ];
}
