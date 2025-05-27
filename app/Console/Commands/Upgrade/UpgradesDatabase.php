<?php

/**
 * UpgradeDatabase.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Upgrade;

use Illuminate\Support\Facades\Log;
use Safe\Exceptions\InfoException;

use function Safe\set_time_limit;

try {
    set_time_limit(0);
} catch (InfoException) {
    Log::warning('set_time_limit returned false. This could be an issue, unless you also run XDebug.');
}

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;

class UpgradesDatabase extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Upgrades the database to the latest version.';
    protected $signature   = 'firefly-iii:upgrade-database {--F|force : Force all upgrades.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->callInitialCommands();
        $commands = [
            'upgrade:480-transaction-identifiers',
            'upgrade:480-migrate-to-groups',
            'upgrade:480-account-currencies',
            'upgrade:480-transfer-currencies',
            'upgrade:480-currency-information',
            'upgrade:480-notes',
            'upgrade:480-attachments',
            'upgrade:480-bills-to-rules',
            'upgrade:480-budget-limit-currencies',
            'upgrade:480-cc-liabilities',
            'upgrade:480-journal-meta-data',
            'upgrade:480-account-meta',
            'upgrade:481-recurrence-meta',
            'upgrade:500-tag-locations',
            'upgrade:560-liabilities',
            'upgrade:600-liabilities',
            'upgrade:550-budget-limit-periods',
            'upgrade:600-rule-actions',
            'upgrade:610-account-balance',
            'upgrade:610-currency-preferences',
            'upgrade:610-currency-preferences',
            'upgrade:620-piggy-banks',
            'upgrade:620-native-amounts',
            'firefly-iii:correct-database',
        ];
        $args     = [];
        if ($this->option('force')) {
            $args = ['--force' => true];
        }
        foreach ($commands as $command) {
            $this->friendlyLine(sprintf('Now executing %s', $command));
            $this->call($command, $args);
        }
        // set new DB version.
        app('fireflyconfig')->set('db_version', (int) config('firefly.db_version'));
        // index will set FF3 version.
        app('fireflyconfig')->set('ff3_version', (string) config('firefly.version'));

        return 0;
    }

    private function callInitialCommands(): void
    {
        $this->call('migrate', ['--seed' => true, '--force' => true, '--no-interaction' => true]);
        $this->call('upgrade:600-pgsql-sequences');
        $this->call('upgrade:480-decrypt-all');
    }
}
