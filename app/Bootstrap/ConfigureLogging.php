<?php
/**
 * ConfigureLogging.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Bootstrap;

use Illuminate\Log\Writer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\ConfigureLogging as IlluminateConfigureLogging;

/**
 * Class ConfigureLogging
 *
 * @package FireflyIII\Bootstrap
 */
class ConfigureLogging extends IlluminateConfigureLogging
{
    /**
     * @param Application $app
     * @param Writer      $log
     */
    protected function configureSingleHandler(Application $app, Writer $log)
    {
        $log->useFiles($app->storagePath().'/logs/firefly-iii.log');
    }

    /**
     * @param Application $app
     * @param Writer      $log
     */
    protected function configureDailyHandler(Application $app, Writer $log)
    {
        $log->useDailyFiles(
            $app->storagePath().'/logs/firefly-iii.log',
            $app->make('config')->get('app.log_max_files', 5)
        );
    }
}