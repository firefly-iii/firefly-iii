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

            // also just in case, some integrity commands:
            //            'upgrade:restore-oauth-keys',
            //            'upgrade:add-timezones-to-dates',
            //            'upgrade:create-group-memberships',
            //            'upgrade:upgrade-group-information',
            //            'upgrade:610-currency-preferences',
            //            'upgrade:620-piggy-banks',
            'firefly-iii:fix-piggies',
            'firefly-iii:create-link-types',
            'firefly-iii:create-access-tokens',
            'firefly-iii:remove-bills',
            'firefly-iii:fix-amount-pos-neg',
            'firefly-iii:enable-currencies',
            'firefly-iii:fix-transfer-budgets',
            'firefly-iii:fix-uneven-amount',
            'firefly-iii:delete-zero-amount',
            'firefly-iii:delete-orphaned-transactions',
            'firefly-iii:delete-empty-journals',
            'firefly-iii:delete-empty-groups',
            'firefly-iii:fix-account-types',
            'firefly-iii:fix-ibans',
            'firefly-iii:fix-account-order',
            'firefly-iii:rename-meta-fields',
            'firefly-iii:fix-ob-currencies',
            'firefly-iii:fix-long-descriptions',
            'firefly-iii:fix-recurring-transactions',
            'firefly-iii:upgrade-group-information',
            // 'firefly-iii:fix-transaction-types', // very resource heavy.
            'firefly-iii:fix-frontpage-accounts',
            // new!
            'firefly-iii:unify-group-accounts',
            'firefly-iii:trigger-credit-recalculation',
            'firefly-iii:migrate-preferences',
        ];
        foreach ($commands as $command) {
            $this->friendlyLine(sprintf('Now executing command "%s"', $command));
            $this->call($command);
        }

        return 0;
    }
}
