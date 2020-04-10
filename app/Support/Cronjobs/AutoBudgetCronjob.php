<?php
declare(strict_types=1);
/**
 * AutoBudgetCronjob.php
 * Copyright (c) 2020 thegrumpydictator@gmail.com
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
use FireflyIII\Jobs\CreateAutoBudgetLimits;
use FireflyIII\Models\Configuration;
use Log;

/**
 * Class AutoBudgetCronjob
 */
class AutoBudgetCronjob extends AbstractCronjob
{

    /**
     * @inheritDoc
     */
    public function fire(): bool
    {
        /** @var Configuration $config */
        $config        = app('fireflyconfig')->get('last_ab_job', 0);
        $lastTime      = (int)$config->data;
        $diff          = time() - $lastTime;
        $diffForHumans = Carbon::now()->diffForHumans(Carbon::createFromTimestamp($lastTime), true);
        if (0 === $lastTime) {
            Log::info('Auto budget cron-job has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43200) {
            Log::info(sprintf('It has been %s since the auto budget cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                Log::info('The auto budget cron-job will not fire now.');

                return false;
            }

            // fire job regardless.
            if (true === $this->force) {
                Log::info('Execution of the auto budget cron-job has been FORCED.');
            }
        }

        if ($lastTime > 0 && $diff > 43200) {
            Log::info(sprintf('It has been %s since the auto budget cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireAutoBudget();

        app('preferences')->mark();

        return true;
    }

    /**
     *
     */
    private function fireAutoBudget(): void
    {
        Log::info(sprintf('Will now fire auto budget cron job task for date "%s".', $this->date->format('Y-m-d')));
        /** @var CreateAutoBudgetLimits $job */
        $job = app(CreateAutoBudgetLimits::class);
        $job->setDate($this->date);
        $job->handle();
        app('fireflyconfig')->set('last_ab_job', (int)$this->date->format('U'));
        Log::info('Done with auto budget cron job task.');
    }
}