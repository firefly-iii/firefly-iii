<?php

/**
 * Kernel.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Override;

/**
 * File to make sure commands work.
 */
class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    #[Override]
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    #[Override]
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(
            static function (): void {
                app('log')->error(
                    'Firefly III no longer users the Laravel scheduler to do cron jobs! Please read the instructions at https://docs.firefly-iii.org/'
                );
                echo "\n";
                echo '------------';
                echo "\n";
                echo wordwrap('Firefly III no longer users the Laravel scheduler to do cron jobs! Please read the instructions here:');
                echo "\n";
                echo 'https://docs.firefly-iii.org/';
                echo "\n\n";
                echo 'Disable this cron job!';
                echo "\n";
                echo '------------';
                echo "\n";
            }
        )->daily();
    }
}
