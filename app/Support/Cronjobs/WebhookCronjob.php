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
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Support\Facades\Log;

/**
 * Class WebhookCronjob
 */
class WebhookCronjob extends AbstractCronjob
{
    /**
     * @throws FireflyException
     */
    public function fire(): void
    {
        Log::debug(sprintf('Now in %s', __METHOD__));

        /** @var Configuration $config */
        $config        = FireflyConfig::get('last_webhook_job', 0);
        $lastTime      = (int) $config->data;
        $diff          = Carbon::now()->getTimestamp() - $lastTime;
        $diffForHumans = today(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);

        if (0 === $lastTime) {
            Log::info('The webhook cron-job has never fired before.');
        }
        // less than ten minutes ago.
        if ($lastTime > 0 && $diff <= 600) {
            Log::info(sprintf('It has been %s since the webhook cron-job has fired.', $diffForHumans));
            if (false === $this->force) {
                Log::info('The cron-job will not fire now.');
                $this->message      = sprintf('It has been %s since the webhook cron-job has fired. It will not fire now.', $diffForHumans);
                $this->jobFired     = false;
                $this->jobErrored   = false;
                $this->jobSucceeded = false;

                return;
            }

            Log::info('Execution of the webhook cron-job has been FORCED.');
        }

        if ($lastTime > 0 && $diff > 600) {
            Log::info(sprintf('It has been %s since the webhook cron-job has fired. It will fire now!', $diffForHumans));
        }

        $this->fireWebhookmessages();

        app('preferences')->mark();
    }

    private function fireWebhookmessages(): void
    {
        Log::info(sprintf('Will now send webhook messages for date "%s".', $this->date->format('Y-m-d H:i:s')));

        Log::debug('send event RequestedSendWebhookMessages through cron job.');
        event(new RequestedSendWebhookMessages());

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Send webhook messages cron job fired successfully.';

        FireflyConfig::set('last_webhook_job', (int) $this->date->format('U'));
        Log::info(sprintf('Marked the last time this job has run as "%s" (%d)', $this->date->format('Y-m-d H:i:s'), (int) $this->date->format('U')));
        Log::info('Done with webhook cron job task.');
    }
}
