<?php

namespace FireflyIII\Providers;

use Auth;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;


/**
 * Class RuleGroupServiceProvider
 *
 * @package FireflyIII\Providers
 */
class RuleGroupServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && Auth::check()) {
                    return app('FireflyIII\Repositories\RuleGroup\RuleGroupRepository', [Auth::user()]);
                } else {
                    if (!isset($arguments[0]) && !Auth::check()) {
                        throw new FireflyException('There is no user present.');
                    }
                }

                return app('FireflyIII\Repositories\RuleGroup\RuleGroupRepository', $arguments);
            }
        );
    }
}
