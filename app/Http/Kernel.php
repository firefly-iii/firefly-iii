<?php namespace FireflyIII\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

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
     * @var array
     */
    protected $middleware
        = [
            'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
            'Illuminate\Cookie\Middleware\EncryptCookies',
            'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
            'Illuminate\Session\Middleware\StartSession',
            'Illuminate\View\Middleware\ShareErrorsFromSession',
            'FireflyIII\Http\Middleware\ReplaceTestVars',
            'FireflyIII\Http\Middleware\VerifyCsrfToken',
        ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware
        = [
            'auth'       => 'FireflyIII\Http\Middleware\Authenticate',
            'auth.basic' => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
            'guest'      => 'FireflyIII\Http\Middleware\RedirectIfAuthenticated',
            'range'      => 'FireflyIII\Http\Middleware\Range',
            'cleanup'    => 'FireflyIII\Http\Middleware\Cleanup',
            'reminders'  => 'FireflyIII\Http\Middleware\Reminders',
            'piggybanks' => 'FireflyIII\Http\Middleware\PiggyBanks',

        ];

}
