<?php

/*
 * SetLatestVersion.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;

class SetsLatestVersion extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Set latest version in DB.';

    protected $signature   = 'firefly-iii:set-latest-version {--james-is-cool}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('james-is-cool')) {
            $this->friendlyError('Am too!');

            return 0;
        }
        app('fireflyconfig')->set('db_version', config('firefly.db_version'));
        app('fireflyconfig')->set('ff3_version', config('firefly.version'));
        $this->friendlyInfo('Updated version.');

        return 0;
    }
}
