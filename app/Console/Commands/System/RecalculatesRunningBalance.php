<?php

declare(strict_types=1);
/*
 * RecalculatesRunningBalance.php
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;

class RecalculatesRunningBalance extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes all running balances. May take a long time to run if forced.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'firefly-iii:refresh-running-balance {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (true === config('firefly.feature_flags.running_balance_column')) {
            $this->friendlyInfo('Will recalculate account balances. This may take a LONG time. Please be patient.');
            $this->correctBalanceAmounts($this->option('force'));
            $this->friendlyInfo('Done recalculating account balances.');

            return 0;
        }
        $this->friendlyWarning('This command has been disabled.');
    }

    private function correctBalanceAmounts(bool $forced): void
    {
        AccountBalanceCalculator::recalculateAll($forced);
    }
}
