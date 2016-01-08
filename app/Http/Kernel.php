<?php

namespace FireflyIII\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

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
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups
        = [
            'web' => [
//                \FireflyIII\Http\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \FireflyIII\Http\Middleware\VerifyCsrfToken::class,
            ],

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
            'auth'       => \FireflyIII\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'guest'      => \FireflyIII\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle'   => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'range'      => \FireflyIII\Http\Middleware\Range::class,
        ];
}
