<?php
/**
 * DeleteEmptyGroups.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Log;

/**
 * Class DeleteEmptyGroups
 */
class DeleteEmptyGroups extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete empty transaction groups.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:delete-empty-groups';

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception;
     */
    public function handle(): int
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $start  = microtime(true);
        $groupIds  =
            TransactionGroup
            ::leftJoin('transaction_journals','transaction_groups.id','=','transaction_journals.transaction_group_id')
            ->whereNull('transaction_journals.id')->get(['transaction_groups.id'])->pluck('id')->toArray();

        $total = count($groupIds);
        Log::debug(sprintf('Count is %d', $total));
        if ($total > 0) {
            $this->info(sprintf('Deleted %d empty transaction group(s).', $total));

            // again, chunks for SQLite.
            $chunks = array_chunk($groupIds, 500);
            foreach ($chunks as $chunk) {
                TransactionGroup::whereNull('deleted_at')->whereIn('id', $chunk)->delete();
            }
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Verified empty groups in %s seconds', $end));

        return 0;
    }
}
