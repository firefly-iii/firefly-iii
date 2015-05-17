<?php namespace FireflyIII\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @codeCoverageIgnore
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
        ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
    }

}
