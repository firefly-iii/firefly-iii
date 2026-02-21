<?php
/*
 * ChecksForUpdates.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Tools;

use Carbon\Carbon;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Services\FireflyIIIOrg\Update\UpdateRequestInterface;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Console\Command;

class ChecksForUpdates extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:check-for-updates {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for Firefly III updates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $build   = Carbon::createFromTimestamp(config('firefly.build_time'), config('app.timezone'));
        $version = config('firefly.version');

        $this->friendlyLine(sprintf('You are running version "%s", built on %s', $version, $build->format('Y-m-d H:i')));
        $permission = FireflyConfig::get('permission_update_check', -1)->data;
        if (1 !== $permission && false === $this->option('force')) {
            $this->friendlyWarning('Checking for updates is disabled. To overrule, use --force.');
            return Command::SUCCESS;
        }
        if (str_contains(config('firefly.version'), 'develop')) {
            $this->friendlyWarning('You are running a development version.');
        }

        /** @var UpdateRequestInterface $request */
        $request = app(UpdateRequestInterface::class);
        // stable, alpha or beta
        $info = $request->getUpdateInformation($version, $build, 'stable');

        if ('' !== $info->getError()) {
            $this->friendlyError($info->getError());
            return Command::FAILURE;
        }
        if (!$info->isNewVersionAvailable()) {
            $this->friendlyInfo(trans('firefly.no_new_release_available'));
            return Command::SUCCESS;
        }
        // if running develop, slightly different message.
        if (str_contains($version, 'develop')) {
            $this->friendlyInfo(trans('firefly.update_current_dev_older', ['version' => $version, 'new_version' => $info->getNewVersion()]));
            return Command::SUCCESS;
        }
        $this->friendlyInfo(trans('firefly.update_new_version_alert', ['your_version' => $version, 'new_version' => $info->getNewVersion(), 'date' => $info->getPublishedAt()->format('Y-m-d H:i:s')]));
        return Command::SUCCESS;
    }
}
