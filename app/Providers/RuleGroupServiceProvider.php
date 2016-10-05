<?php
/**
 * RuleGroupServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace FireflyIII\Providers;

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
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\RuleGroup\RuleGroupRepository', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Repositories\RuleGroup\RuleGroupRepository', $arguments);
            }
        );
    }
}
