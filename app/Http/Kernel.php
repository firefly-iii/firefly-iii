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

use FireflyIII\Http\Middleware\Authenticate;
use FireflyIII\Http\Middleware\Binder;
use FireflyIII\Http\Middleware\InstallationId;
use FireflyIII\Http\Middleware\RedirectIfAuthenticated;
use FireflyIII\Http\Middleware\StartFireflySession;
use FireflyIII\Http\Middleware\TrimStrings;
use FireflyIII\Http\Middleware\TrustProxies;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Class Kernel
 */
class Kernel extends HttpKernel
{
    protected $middleware
                                  = [
            // SecureHeaders::class,
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
    protected $middlewarePriority = [StartFireflySession::class, ShareErrorsFromSession::class, Authenticate::class, Binder::class, Authorize::class];
}
