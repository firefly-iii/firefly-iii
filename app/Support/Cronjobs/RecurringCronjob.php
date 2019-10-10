<?php
/**
 * RecurringCronjob.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Cronjobs;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Jobs\CreateRecurringTransactions;
use FireflyIII\Models\Configuration;
use Log;

/**
 * Class RecurringCronjob
 */
class RecurringCronjob extends AbstractCronjob
{
    /** @var bool */
    private $force;

    /** @var Carbon */
    private $date;

    /**
     * RecurringCronjob constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->force = false;
        $this->date  = new Carbon;
    }

    /**
     * @param bool $force
     */
    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    /**
     * @param Carbon $date
     */
    public function setDate(Carbon $date): void
    {
        $this->date = $date;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    public function fire(): bool
    {
        /** @var Configuration $config */
        $config        = app('fireflyconfig')->get('last_rt_job', 0);
        $lastTime      = (int)$config->data;
        $diff          = time() - $lastTime;
        $diffForHumans = Carbon::now()->diffForHumans(Carbon::createFromTimestamp($lastTime), true);
        if (0 === $lastTime) {
            Log::info('Recurring transactions cron-job has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43200) {
            Log::info(sprintf('It has been %s since the recurring transactions cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                Log::info('The cron-job will not fire now.');

                return false;
            }

            // fire job regardless.
            if (true === $this->force) {
                Log::info('Execution of the recurring transaction cron-job has been FORCED.');
            }
        }

        if ($lastTime > 0 && $diff > 43200) {
            Log::info(sprintf('It has been %s since the recurring transactions cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireRecurring();

        app('preferences')->mark();

        return true;
    }

    /**
     *
     */
    private function fireRecurring(): void
    {
        Log::info(sprintf('Will now fire recurring cron job task for date "%s".', $this->date->format('Y-m-d')));
        /** @var CreateRecurringTransactions $job */
        $job = app(CreateRecurringTransactions::class);
        $job->setDate($this->date);
        $job->setForce($this->force);
        $job->handle();
        app('fireflyconfig')->set('last_rt_job', (int)$this->date->format('U'));
        Log::info('Done with recurring cron job task.');
    }
}
