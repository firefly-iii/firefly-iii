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
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @package FireflyIII\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands
        = [
            UpgradeFireflyInstructions::class,
        ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    protected function schedule(Schedule $schedule)
    {
    }
}
