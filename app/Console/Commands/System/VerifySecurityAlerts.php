<?php

/*
 * VerifySecurityAlerts.php
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
use Illuminate\Database\QueryException;
use League\Flysystem\FilesystemException;

class VerifySecurityAlerts extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Verify security alerts';

    protected $signature   = 'firefly-iii:verify-security-alerts';

    /**
     * Execute the console command.
     *
     * @throws FilesystemException
     */
    public function handle(): int
    {
        $this->removeOldAdvisory();

        // check for security advisories.
        $version = config('firefly.version');
        $disk    = \Storage::disk('resources');
        // Next line is ignored because it's a Laravel Facade.
        if (!$disk->has('alerts.json')) { // @phpstan-ignore-line
            app('log')->debug('No alerts.json file present.');

            return 0;
        }
        $content = $disk->get('alerts.json');
        $json    = json_decode($content, true, 10);

        /** @var array $array */
        foreach ($json as $array) {
            if ($version === $array['version'] && true === $array['advisory']) {
                app('log')->debug(sprintf('Version %s has an alert!', $array['version']));
                // add advisory to configuration.
                $this->saveSecurityAdvisory($array);

                // depends on level
                if ('info' === $array['level']) {
                    app('log')->debug('INFO level alert');
                    $this->friendlyInfo($array['message']);

                    return 0;
                }
                if ('warning' === $array['level']) {
                    app('log')->debug('WARNING level alert');
                    $this->friendlyWarning('------------------------ :o');
                    $this->friendlyWarning($array['message']);
                    $this->friendlyWarning('------------------------ :o');

                    return 0;
                }
                if ('danger' === $array['level']) {
                    app('log')->debug('DANGER level alert');
                    $this->friendlyError('------------------------ :-(');
                    $this->friendlyError($array['message']);
                    $this->friendlyError('------------------------ :-(');

                    return 0;
                }

                return 0;
            }
        }
        app('log')->debug(sprintf('No security alerts for version %s', $version));
        $this->friendlyPositive(sprintf('No security alerts for version %s', $version));

        return 0;
    }

    private function removeOldAdvisory(): void
    {
        try {
            app('fireflyconfig')->delete('upgrade_security_message');
            app('fireflyconfig')->delete('upgrade_security_level');
        } catch (QueryException $e) {
            app('log')->debug(sprintf('Could not delete old security advisory, but thats OK: %s', $e->getMessage()));
        }
    }

    private function saveSecurityAdvisory(array $array): void
    {
        try {
            app('fireflyconfig')->set('upgrade_security_message', $array['message']);
            app('fireflyconfig')->set('upgrade_security_level', $array['level']);
        } catch (QueryException $e) {
            app('log')->debug(sprintf('Could not save new security advisory, but thats OK: %s', $e->getMessage()));
        }
    }
}
