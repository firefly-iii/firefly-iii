<?php
declare(strict_types = 1);

namespace FireflyIII\Providers;

use Illuminate\Support\ServiceProvider;
use Log;

/**
 * Class AppServiceProvider
 *
 * @package FireflyIII\Providers
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // force https urls
        if (env('APP_FORCE_SSL', false)) {
            \URL::forceSchema('https');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // make sure the logger doesn't log everything when it doesn't need to.
        $monolog = Log::getMonolog();
        foreach ($monolog->getHandlers() as $handler) {
            $handler->setLevel(config('app.log-level'));
        }
    }
}
