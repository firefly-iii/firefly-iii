<?php

/**
 * FixGroupAccounts.php
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

use DB;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Handlers\Events\UpdatedGroupEventHandler;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;

/**
 * Class FixGroupAccounts
 */
class FixGroupAccounts extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unify the source / destination accounts of split groups.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:unify-group-accounts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $groups = [];
        $res    = TransactionJournal
            ::groupBy('transaction_group_id')
            ->get(['transaction_group_id', DB::raw('COUNT(transaction_group_id) as the_count')]);
        foreach ($res as $journal) {
            if ((int)$journal->the_count > 1) {
                $groups[] = (int)$journal->transaction_group_id;
            }
        }
        $handler = new UpdatedGroupEventHandler;
        foreach ($groups as $groupId) {
            $group = TransactionGroup::find($groupId);
            $event = new UpdatedTransactionGroup($group);
            $handler->unifyAccounts($event);
        }

        $this->line('Updated inconsistent transaction groups.');

        return 0;
    }
}
