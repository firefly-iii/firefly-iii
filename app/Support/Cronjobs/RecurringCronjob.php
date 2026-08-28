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
use FireflyIII\Jobs\CreateRecurringTransactions;
use FireflyIII\Models\Configuration;
use FireflyIII\Support\Facades\AppConfiguration;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;

/**
 * Class RecurringCronjob
 */
class RecurringCronjob extends AbstractCronjob
{
    /**
     * @throws FireflyException
     */
    public function fire(): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        /** @var User $user */
        foreach ($this->users as $user) {
            /** @var Configuration $config */
            $config        = AppConfiguration::get(sprintf('last_rt_job_%d', $user->id), 0);
            $lastTime      = (int) $config->data;
            $diff          = now(config('app.timezone'))->getTimestamp() - $lastTime;
            $diffForHumans = now(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);

            if (0 === $lastTime) {
                Log::info(sprintf('Recurring transactions cron-job has never fired before for user #%d.', $user->id));
            }
            // less than half a day ago:
            if ($lastTime > 0 && $diff <= 43_200) {
                Log::info(sprintf('It has been "%s" since the recurring transactions cron-job has fired for user #%d.', $diffForHumans, $user->id));
                if (false === $this->force || false === $user->hasRole('owner')) {
                    Log::info(sprintf('The cron-job will not fire now for user #%d.', $user->id));
                    $this->message      = sprintf(
                        'It has been "%s" since the recurring transactions cron-job has fired for user #%d. It will not fire now.',
                        $diffForHumans,
                        $user->id
                    );
                    $this->jobFired     = false;
                    $this->jobErrored   = false;
                    $this->jobSucceeded = false;

                    return;
                }
                Log::info(sprintf('Execution of the recurring transaction cron-job has been FORCED for user #%d.', $user->id));
            }

            if ($lastTime > 0 && $diff > 43_200) {
                Log::info(sprintf(
                    'It has been "%s" since the recurring transactions cron-job has fired. It will fire now for user #%d!',
                    $diffForHumans,
                    $user->id
                ));
            }

            $this->fireRecurring($user);
        }
        Preferences::mark();
    }

    private function fireRecurring(User $user): void
    {
        Log::info(sprintf('Will now fire recurring cron job task for date "%s".', $this->date->format('Y-m-d H:i:s')));

        $job                = new CreateRecurringTransactions($this->date);
        $job->setForce($this->force);
        $job->setUser($user);
        $job->handle();

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Recurring transactions cron job fired successfully.';

        AppConfiguration::set(sprintf('last_rt_job_%d', $user->id), (int) $this->date->format('U'));
        Log::info(sprintf(
            'Marked the last time this job has run as "%s" (%d) for user #%d',
            $this->date->format('Y-m-d H:i:s'),
            (int) $this->date->format('U'),
            $user->id
        ));
        Log::info('Done with recurring cron job task.');
    }
}
