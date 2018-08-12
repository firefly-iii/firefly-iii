<?php
/**
 * RecurringCronjob.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
            Log::info('Recurring transactions cronjob has never fired before.');
        }
        // less than half a day ago:
        if ($lastTime > 0 && $diff <= 43200) {
            Log::info(sprintf('It has been %s since the recurring transactions cronjob has fired. It will not fire now.', $diffForHumans));

            return false;
        }

        if ($lastTime > 0 && $diff > 43200) {
            Log::info(sprintf('It has been %s since the recurring transactions cronjob has fired. It will fire now!', $diffForHumans));
        }

        try {
            $this->fireRecurring();
        } catch (FireflyException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException(sprintf('Could not run recurring transaction cron job: %s', $e->getMessage()));
        }

        return true;
    }

    /**
     *
     * @throws FireflyException
     */
    private function fireRecurring(): void
    {
        $job = new CreateRecurringTransactions(new Carbon);
        $job->handle();
        app('fireflyconfig')->set('last_rt_job', time());
    }
}