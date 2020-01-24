<?php

/**
 * Cron.php
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

namespace FireflyIII\Console\Commands\Tools;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Class Cron
 *
 * @codeCoverageIgnore
 */
class Cron extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs all Firefly III cron-job related commands. Configure a cron job according to the official Firefly III documentation.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:cron
        {--F|force : Force the cron job(s) to execute.}
        {--date= : Set the date in YYYY-MM-DD to make Firefly III think that\'s the current date.}
        ';

    /**
     * @return int
     * @throws Exception
     */
    public function handle(): int
    {
        $date = null;
        try {
            $date = new Carbon($this->option('date'));
        } catch (InvalidArgumentException $e) {
            $this->error(sprintf('"%s" is not a valid date', $this->option('date')));
            $e->getMessage();
        }


        $recurring = new RecurringCronjob;
        $recurring->setForce($this->option('force'));

        // set date in cron job:
        if (null !== $date) {
            $recurring->setDate($date);
        }

        try {
            $result = $recurring->fire();
        } catch (FireflyException $e) {
            $this->error($e->getMessage());

            return 0;
        }
        if (false === $result) {
            $this->line('The recurring transaction cron job did not fire.');
        }
        if (true === $result) {
            $this->line('The recurring transaction cron job fired successfully.');
        }

        $this->info('More feedback on the cron jobs can be found in the log files.');

        return 0;
    }


}
