<?php

/**
 * DeleteEmptyGroups.php
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

use Exception;
use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\TransactionGroup;
use Illuminate\Console\Command;

class RemovesEmptyGroups extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Delete empty transaction groups.';
    protected $signature   = 'correction:empty-groups';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $groupIds
               = TransactionGroup::leftJoin('transaction_journals', 'transaction_groups.id', '=', 'transaction_journals.transaction_group_id')
                   ->whereNull('transaction_journals.id')->get(['transaction_groups.id'])->pluck('id')->toArray()
        ;

        $total = count($groupIds);
        if ($total > 0) {
            $this->friendlyInfo(sprintf('Deleted %d empty transaction group(s).', $total));

            // again, chunks for SQLite.
            $chunks = array_chunk($groupIds, 500);
            foreach ($chunks as $chunk) {
                TransactionGroup::whereNull('deleted_at')->whereIn('id', $chunk)->delete();
            }
        }

        return 0;
    }
}
