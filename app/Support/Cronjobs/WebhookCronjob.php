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
use FireflyIII\Events\Model\Webhook\WebhookMessagesRequestSending;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use FireflyIII\Support\Facades\AppConfiguration;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\User;
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

        /** @var User $user */
        foreach ($this->users as $user) {
            /** @var Configuration $config */
            $config        = AppConfiguration::get(sprintf('last_webhook_job_%d', $user->id), 0);
            $lastTime      = (int) $config->data;
            $diff          = now(config('app.timezone'))->getTimestamp() - $lastTime;
            $diffForHumans = now(config('app.timezone'))->diffForHumans(Carbon::createFromTimestamp($lastTime), null, true);

            if (0 === $lastTime) {
                Log::info(sprintf('The webhook cron-job has never fired before for user #%d.', $user->id));
            }
            // less than ten minutes ago.
            if ($lastTime > 0 && $diff <= 600) {
                Log::info(sprintf('It has been %s since the webhook cron-job has fired for user #%d.', $diffForHumans, $user->id));
                if (false === $this->force || false === $user->hasRole('owner')) {
                    Log::info(sprintf('The cron-job will not fire now for user #%d.', $user->id));
                    $this->message      = sprintf('It has been %s since the webhook cron-job has fired. It will not fire now.', $diffForHumans);
                    $this->jobFired     = false;
                    $this->jobErrored   = false;
                    $this->jobSucceeded = false;

                    return;
                }

                Log::info(sprintf('Execution of the webhook cron-job has been FORCED for user #%d.', $user->id));
            }

            if ($lastTime > 0 && $diff > 600) {
                Log::info(sprintf('It has been %s since the webhook cron-job has fired for user #%d. It will fire now!', $diffForHumans, $user->id));
            }

            $this->fireWebhookMessages($user);
        }

        Preferences::mark();
    }

    private function fireWebhookMessages(User $user): void
    {
        Log::info(sprintf('Will now send webhook messages for date "%s" and user #%d.', $this->date->format('Y-m-d H:i:s'), $user->id));

        Log::debug(sprintf('send event WebhookMessagesRequestSending from %s', __METHOD__));
        event(new WebhookMessagesRequestSending());

        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = true;
        $this->message      = 'Send webhook messages cron job fired successfully.';

        AppConfiguration::set(sprintf('last_webhook_job_%d', $user->id), (int) $this->date->format('U'));
        Log::info(sprintf('Marked the last time this job has run as "%s" (%d)', $this->date->format('Y-m-d H:i:s'), (int) $this->date->format('U')));
        Log::info('Done with webhook cron job task.');
    }
}
