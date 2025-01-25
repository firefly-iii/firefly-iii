<?php

/**
 * RecurringCronjob.php
 * Copyright (c) 2019 james@firefly-iii.org
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
use FireflyIII\Jobs\WarnAboutBills;
use FireflyIII\Models\Configuration;

/**
 * Class BillWarningCronjob
 */
class BillWarningCronjob extends AbstractCronjob
{
    /**
     * @throws FireflyException
     */
    public function fire(): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        /** @var Configuration $config */
        $config        = app('fireflyconfig')->get('last_bw_job', 0);
        $lastTime      = (int) $config->data;
        $diff          = time() - $lastTime;
        $diffForHumans = today(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);

        if (0 === $lastTime) {
            app('log')->info('The bill notification cron-job has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43200) {
            app('log')->info(sprintf('It has been %s since the bill notification cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                app('log')->info('The cron-job will not fire now.');
                $this->message      = sprintf('It has been %s since the bill notification cron-job has fired. It will not fire now.', $diffForHumans);
                $this->jobFired     = false;
                $this->jobErrored   = false;
                $this->jobSucceeded = false;

                return;
            }

            app('log')->info('Execution of the bill notification cron-job has been FORCED.');
        }

        if ($lastTime > 0 && $diff > 43200) {
            app('log')->info(sprintf('It has been %s since the bill notification cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireWarnings();

        app('preferences')->mark();
    }

    private function fireWarnings(): void
    {
        app('log')->info(sprintf('Will now fire bill notification job task for date "%s".', $this->date->format('Y-m-d H:i:s')));

        /** @var WarnAboutBills $job */
        $job                = app(WarnAboutBills::class);
        $job->setDate($this->date);
        $job->setForce($this->force);
        $job->handle();

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Bill notification cron job fired successfully.';

        app('fireflyconfig')->set('last_bw_job', (int) $this->date->format('U'));
        app('log')->info(sprintf('Marked the last time this job has run as "%s" (%d)', $this->date->format('Y-m-d H:i:s'), (int) $this->date->format('U')));
        app('log')->info('Done with bill notification cron job task.');
    }
}
