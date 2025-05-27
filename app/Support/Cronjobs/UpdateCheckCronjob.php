<?php

/*
 * UpdateCheckCronjob.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Cronjobs;

use FireflyIII\Helpers\Update\UpdateTrait;
use FireflyIII\Models\Configuration;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Support\Facades\Log;
use Override;

class UpdateCheckCronjob extends AbstractCronjob
{
    use UpdateTrait;

    #[Override]
    public function fire(): void
    {
        Log::debug('Now in checkForUpdates()');

        // should not check for updates:
        $permission         = app('fireflyconfig')->get('permission_update_check', -1);
        $value              = (int) $permission->data;
        if (1 !== $value) {
            Log::debug('Update check is not enabled.');
            // get stuff from job:
            $this->jobFired     = false;
            $this->jobErrored   = false;
            $this->jobSucceeded = true;
            $this->message      = 'The update check is not enabled.';

            return;
        }

        // TODO this is duplicate.
        /** @var Configuration $lastCheckTime */
        $lastCheckTime      = FireflyConfig::get('last_update_check', time());
        $now                = time();
        $diff               = $now - $lastCheckTime->data;
        Log::debug(sprintf('Last check time is %d, current time is %d, difference is %d', $lastCheckTime->data, $now, $diff));
        if ($diff < 604800 && false === $this->force) {
            // get stuff from job:
            $this->jobFired     = false;
            $this->jobErrored   = false;
            $this->jobSucceeded = true;
            $this->message      = sprintf('Checked for updates less than a week ago (on %s).', date('Y-m-d H:i:s', $lastCheckTime->data));

            return;
        }
        // last check time was more than a week ago.
        Log::debug('Have not checked for a new version in a week!');
        $release            = $this->getLatestRelease();
        if ('error' === $release['level']) {
            // get stuff from job:
            $this->jobFired     = true;
            $this->jobErrored   = true;
            $this->jobSucceeded = false;
            $this->message      = $release['message'];

            return;
        }
        // get stuff from job:
        $this->jobFired     = true;
        $this->jobErrored   = false;
        $this->jobSucceeded = false;
        $this->message      = $release['message'];
    }
}
