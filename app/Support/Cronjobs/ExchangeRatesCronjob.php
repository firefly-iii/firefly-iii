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
use FireflyIII\Support\Facades\AppConfiguration;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

/**
 * Class ExchangeRatesCronjob
 */
class ExchangeRatesCronjob extends AbstractCronjob
{
    public function fire(): void
    {
        /** @var User $user */
        foreach($this->users as $user) {
            $key = sprintf('last_cer_job_%d', $user->id);
            /** @var Configuration $config */
            $config        = AppConfiguration::get($key, 0);
            $lastTime      = (int) $config->data;
            $diff          = now(config('app.timezone'))->getTimestamp() - $lastTime;
            $diffForHumans = now(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);
            if (0 === $lastTime) {
                Log::info(sprintf('Exchange rates cron-job has never fired before for user #%d.', $user->id));
            }
            // less than half a day ago:
            if ($lastTime > 0 && $diff <= 43_200) {
                Log::info(sprintf('It has been %s since the exchange rates cron-job has fired for user #%d.', $diffForHumans, $user->id));
                if (false === $this->force || false === $user->hasRole('owner')) {
                    Log::info(sprintf('The exchange rates cron-job will not fire now for user #%d.', $user->id));
                    $this->message = sprintf('It has been %s since the exchange rates cron-job has fired for user #%d. It will not fire now.', $diffForHumans, $user->id);
                    continue;
                }

                Log::info(sprintf('Execution of the exchange rates cron-job has been FORCED for user #%d.', $user->id));
            }

            if ($lastTime > 0 && $diff > 43_200) {
                Log::info(sprintf('It has been %s since the exchange rates cron-job has fired for user #%d. It will fire now!', $diffForHumans, $user->id));
            }

            $this->fireExchangeRateJob($user);
            Preferences::mark();
        }
    }

    private function fireExchangeRateJob(User $user): void
    {
        Log::info(sprintf('Will now fire exchange rates cron job task for date "%s" and user #%d.', $this->date->format('Y-m-d'), $user->id));

        /** @var DownloadExchangeRates $job */
        $job                = app(DownloadExchangeRates::class);
        $job->setDate($this->date);
        $job->setUser($user);
        $job->handle();

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Exchange rates cron job fired successfully.';

        AppConfiguration::set(sprintf('last_cer_job_%d', $user->id), (int) $this->date->format('U'));
        Log::info(sprintf('Marked the last time this job has run as "%s" (%d) for user #%d', $this->date->format('Y-m-d H:i:s'), (int) $this->date->format('U'), $user->id));
        Log::info('Done with exchange rates job task.');
    }
}
