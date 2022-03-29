<?php

/*
 * VerifySecurityAlerts.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;
use Log;
use Storage;

/**
 * Class VerifySecurityAlerts
 */
class VerifySecurityAlerts extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify security alerts';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:verify-security-alerts';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \League\Flysystem\FilesystemException
     */
    public function handle(): int
    {
        // remove old advisory
        app('fireflyconfig')->delete('upgrade_security_message');
        app('fireflyconfig')->delete('upgrade_security_level');

        // check for security advisories.
        $version = config('firefly.version');
        $disk    = Storage::disk('resources');
        if (!$disk->has('alerts.json')) {
            Log::debug('No alerts.json file present.');

            return 0;
        }
        $content = $disk->get('alerts.json');
        $json    = json_decode($content, true, 10);

        /** @var array $array */
        foreach ($json as $array) {
            if ($version === $array['version'] && true === $array['advisory']) {
                Log::debug(sprintf('Version %s has an alert!', $array['version']));
                // add advisory to configuration.
                app('fireflyconfig')->set('upgrade_security_message', $array['message']);
                app('fireflyconfig')->set('upgrade_security_level', $array['level']);

                // depends on level
                if ('info' === $array['level']) {
                    Log::debug('INFO level alert');
                    $this->info($array['message']);

                    return 0;
                }
                if ('warning' === $array['level']) {
                    Log::debug('WARNING level alert');
                    $this->warn('------------------------ :o');
                    $this->warn($array['message']);
                    $this->warn('------------------------ :o');

                    return 0;
                }
                if ('danger' === $array['level']) {
                    Log::debug('DANGER level alert');
                    $this->error('------------------------ :-(');
                    $this->error($array['message']);
                    $this->error('------------------------ :-(');

                    return 0;
                }

                return 0;
            }
        }
        Log::debug('This version is not mentioned.');

        return 0;
    }
}
