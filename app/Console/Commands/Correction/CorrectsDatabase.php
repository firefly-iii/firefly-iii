<?php

/**
 * CorrectDatabase.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;

class CorrectsDatabase extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Will validate and correct the integrity of your database, if necessary.';
    protected $signature   = 'firefly-iii:correct-database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // if table does not exist, return false
        if (!\Schema::hasTable('users')) {
            $this->friendlyError('No "users"-table, will not continue.');

            return 1;
        }
        $commands = [
            'correction:restore-oauth-keys',
            'correction:timezones',
            'correction:create-group-memberships',
            'correction:group-information',
            'correction:piggy-banks',
            'correction:link-types',
            'correction:access-tokens',
            'correction:bills',
            'correction:amounts',
            'correction:currencies',
            'correction:transfer-budgets',
            'correction:uneven-amounts',
            'correction:zero-amounts',
            'correction:orphaned-transactions',
            'correction:empty-journals',
            'correction:empty-groups',
            'correction:account-types',
            'correction:ibans',
            'correction:account-order',
            'correction:meta-fields',
                        'correction:opening-balance-currencies',
                        'correction:long-descriptions',
                        'correction:recurring-transactions',
                        'correction:frontpage-accounts',
                        'correction:group-accounts',
                        'correction:recalculates-liabilities',
                        'correction:preferences',
            // 'correction:transaction-types', // resource heavy, disabled.
            // 'correction:recalculate-native-amounts', // not necessary, disabled.
            'firefly-iii:report-integrity',
        ];
        foreach ($commands as $command) {
            $this->friendlyLine(sprintf('Now executing command "%s"', $command));
            $this->call($command);
        }

        return 0;
    }
}
