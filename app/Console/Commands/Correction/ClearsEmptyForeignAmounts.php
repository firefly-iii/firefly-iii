<?php

declare(strict_types=1);

/*
 * ClearsEmptyForeignAmounts.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Transaction;
use Illuminate\Console\Command;

class ClearsEmptyForeignAmounts extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'correction:clears-empty-foreign-amounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes references to foreign amounts if there is no amount.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // transaction: has no amount, but reference to currency.
        $count = Transaction::whereNull('foreign_amount')->whereNotNull('foreign_currency_id')->count();
        if ($count > 0) {
            Transaction::whereNull('foreign_amount')->whereNotNull('foreign_currency_id')->update(['foreign_currency_id' => null]);
            $this->friendlyInfo(sprintf('Corrected %d invalid foreign amount reference(s)', $count));
        }
        // transaction: has amount, but no currency.
        $count = Transaction::whereNull('foreign_currency_id')->whereNotNull('foreign_amount')->count();
        if ($count > 0) {
            Transaction::whereNull('foreign_currency_id')->whereNotNull('foreign_amount')->update(['foreign_amount' => null]);
            $this->friendlyInfo(sprintf('Corrected %d invalid foreign amount reference(s)', $count));
        }

        return self::SUCCESS;
    }
}
