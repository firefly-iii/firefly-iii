<?php
declare(strict_types=1);
/**
 * TelemetryCronjob.php
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

namespace FireflyIII\Support\Cronjobs;

use Carbon\Carbon;
use FireflyIII\Jobs\SubmitTelemetryData;
use FireflyIII\Models\Configuration;
use Log;

/**
 * Class TelemetryCronjob
 */
class TelemetryCronjob extends AbstractCronjob
{

    /**
     * @inheritDoc
     */
    public function fire(): bool
    {
        /** @var Configuration $config */
        $config        = app('fireflyconfig')->get('last_tm_job', 0);
        $lastTime      = (int) $config->data;
        $diff          = time() - $lastTime;
        $diffForHumans = Carbon::now()->diffForHumans(Carbon::createFromTimestamp($lastTime), true);
        if (0 === $lastTime) {
            Log::info('Telemetry cron-job has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43200) {
            Log::info(sprintf('It has been %s since the telemetry cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                Log::info('The cron-job will not fire now.');

                return false;
            }

            // fire job regardless.
            if (true === $this->force) {
                Log::info('Execution of the telemetry cron-job has been FORCED.');
            }
        }

        if ($lastTime > 0 && $diff > 43200) {
            Log::info(sprintf('It has been %s since the telemetry cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireTelemetry();

        app('preferences')->mark();

        return true;
    }


    /**
     *
     */
    private function fireTelemetry(): void
    {
        Log::info(sprintf('Will now fire telemetry cron job task for date "%s".', $this->date->format('Y-m-d')));
        /** @var SubmitTelemetryData $job */
        $job = app(SubmitTelemetryData::class);
        $job->setDate($this->date);
        $job->setForce($this->force);
        $job->handle();
        app('fireflyconfig')->set('last_tm_job', (int) $this->date->format('U'));
        Log::info('Done with telemetry cron job task.');
    }
}