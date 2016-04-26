<?php
declare(strict_types = 1);

/**
 * Kernel.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Console;

use FireflyIII\Console\Commands\UpgradeFireflyInstructions;
use FireflyIII\Console\Commands\VerifyDatabase;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @package FireflyIII\Console
 */
class Kernel extends ConsoleKernel
{

    /**
     * The bootstrap classes for the application.
     *
     * This needs to be for with the next upgrade.
     *
     * @var array
     */
    protected $bootstrappers
        = [
            'Illuminate\Foundation\Bootstrap\DetectEnvironment',
            'Illuminate\Foundation\Bootstrap\LoadConfiguration',
            'FireflyIII\Bootstrap\ConfigureLogging',
            'Illuminate\Foundation\Bootstrap\HandleExceptions',
            'Illuminate\Foundation\Bootstrap\RegisterFacades',
            'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
            'Illuminate\Foundation\Bootstrap\RegisterProviders',
            'Illuminate\Foundation\Bootstrap\BootProviders',
        ];

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands
        = [
            UpgradeFireflyInstructions::class,
            VerifyDatabase::class,
        ];
}
