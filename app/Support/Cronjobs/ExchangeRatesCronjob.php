<?php

/**
 * ExchangeRatesCronjob.php
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

namespace FireflyIII\Support\Cronjobs;

use Carbon\Carbon;
use FireflyIII\Jobs\DownloadExchangeRates;
use FireflyIII\Models\Configuration;

/**
 * Class ExchangeRatesCronjob
 */
class ExchangeRatesCronjob extends AbstractCronjob
{
    public function fire(): void
    {
        /** @var Configuration $config */
        $config        = app('fireflyconfig')->get('last_cer_job', 0);
        $lastTime      = (int) $config->data;
        $diff          = time() - $lastTime;
        $diffForHumans = today(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);
        if (0 === $lastTime) {
            app('log')->info('Exchange rates cron-job has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43200) {
            app('log')->info(sprintf('It has been %s since the exchange rates cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                app('log')->info('The exchange rates cron-job will not fire now.');
                $this->message = sprintf('It has been %s since the exchange rates cron-job has fired. It will not fire now.', $diffForHumans);

                return;
            }

            app('log')->info('Execution of the exchange rates cron-job has been FORCED.');
        }

        if ($lastTime > 0 && $diff > 43200) {
            app('log')->info(sprintf('It has been %s since the exchange rates cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireExchangeRateJob();
        app('preferences')->mark();
    }

    private function fireExchangeRateJob(): void
    {
        app('log')->info(sprintf('Will now fire exchange rates cron job task for date "%s".', $this->date->format('Y-m-d')));

        /** @var DownloadExchangeRates $job */
        $job                = app(DownloadExchangeRates::class);
        $job->setDate($this->date);
        $job->handle();

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Exchange rates cron job fired successfully.';

        app('fireflyconfig')->set('last_cer_job', (int) $this->date->format('U'));
        app('log')->info('Done with exchange rates job task.');
    }
}
