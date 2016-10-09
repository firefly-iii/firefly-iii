<?php
/**
 * ConfigureLogging.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\ConfigureLogging as IlluminateConfigureLogging;
use Illuminate\Log\Writer;

/**
 * Class ConfigureLogging
 *
 * @package FireflyIII\Bootstrap
 */
class ConfigureLogging extends IlluminateConfigureLogging
{

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param  \Illuminate\Log\Writer                       $log
     *
     * @return void
     */
    protected function configureDailyHandler(Application $app, Writer $log)
    {
        $config = $app->make('config');

        $maxFiles = $config->get('app.log_max_files');

        $log->useDailyFiles(
            $app->storagePath() . '/logs/firefly-iii.log', is_null($maxFiles) ? 5 : $maxFiles,
            $config->get('app.log_level', 'debug')
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application $app
     * @param  \Illuminate\Log\Writer                       $log
     *
     * @return void
     */
    protected function configureSingleHandler(Application $app, Writer $log)
    {
        $log->useFiles(
            $app->storagePath() . '/logs/firefly-iii.log',
            $app->make('config')->get('app.log_level', 'debug')
        );
    }
}
