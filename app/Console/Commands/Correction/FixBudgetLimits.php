<?php

/*
 * FixBudgetLimits.php
 * Copyright (c) 2022 james@firefly-iii.org
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

use DB;
use FireflyIII\Models\BudgetLimit;
use Illuminate\Console\Command;

/**
 * Class CorrectionSkeleton
 */
class FixBudgetLimits extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes negative budget limits';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-negative-limits';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $set = BudgetLimit::where('amount', '<', '0')->get();
        if (0 === $set->count()) {
            $this->info('All budget limits are OK.');
            return 0;
        }
        $count = BudgetLimit::where('amount', '<', '0')->update(['amount' => DB::raw('amount * -1')]);

        $this->info(sprintf('Fixed %d budget limit(s)', $count));

        return 0;
    }
}
