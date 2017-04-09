<?php
/**
 * LogServiceProvider.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Providers;

use Illuminate\Log\LogServiceProvider as LaravelLogServiceProvider;
use Illuminate\Log\Writer;

/**
 * Class LogServiceProvider
 *
 * @package FireflyIII\Providers
 */
class LogServiceProvider extends LaravelLogServiceProvider
{
    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer $log
     *
     * @return void
     */
    protected function configureDailyHandler(Writer $log)
    {
        $log->useDailyFiles(
            $this->app->storagePath() . '/logs/firefly-iii.log', $this->maxFiles(),
            $this->logLevel()
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer $log
     *
     * @return void
     */
    protected function configureSingleHandler(Writer $log)
    {
        $log->useFiles(
            $this->app->storagePath() . '/logs/firefly-iii.log',
            $this->logLevel()
        );
    }
}
