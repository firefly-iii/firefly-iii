<?php

declare(strict_types=1);

/*
 * CorrectsInversedBudgetLimits.php
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
use FireflyIII\Models\BudgetLimit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrectsInvertedBudgetLimits extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'correction:corrects-inverted-budget-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse budget limits where the dates are inverted.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $set = BudgetLimit::where('start_date', '>', DB::raw('end_date'))->get();
        if (0 === $set->count()) {
            Log::debug('No inverted budget limits found.');

            return Command::SUCCESS;
        }

        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            $start                   = $budgetLimit->start_date->copy();
            $end                     = $budgetLimit->end_date->copy();
            $budgetLimit->start_date = $end;
            $budgetLimit->end_date   = $start;
            $budgetLimit->saveQuietly();
        }
        if (1 === $set->count()) {
            $this->friendlyInfo('Corrected one budget limit to have the right start/end dates.');

            return Command::SUCCESS;
        }
        $this->friendlyInfo(sprintf('Corrected %d budget limits to have the right start/end dates.', count($set)));

        return Command::SUCCESS;
    }
}
