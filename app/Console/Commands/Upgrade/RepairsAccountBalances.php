<?php

/*
 * CorrectAccountBalance.php
 * Copyright (c) 2024 james@firefly-iii.org.
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use Illuminate\Console\Command;

class RepairsAccountBalances extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '610_correct_balances';
    protected $description          = 'Recalculate all account balance amounts';
    protected $signature            = 'upgrade:610-account-balances {--F|force : Force the execution of this command.}';

    public function handle(): int
    {
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }
        if (true === config('firefly.feature_flags.running_balance_column')) {
            $this->friendlyInfo('Will recalculate account balances. This may take a LONG time. Please be patient.');
            $this->markAsExecuted();
            $this->correctBalanceAmounts();
            $this->friendlyInfo('Done recalculating account balances.');

            return 0;
        }
        $this->friendlyWarning('This command has been disabled.');

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);

        return (bool) $configVar?->data;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }

    private function correctBalanceAmounts(): void
    {
        return;
        AccountBalanceCalculator::recalculateAll(true);
    }
}
