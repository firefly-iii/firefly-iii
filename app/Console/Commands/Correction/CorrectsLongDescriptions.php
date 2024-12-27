<?php

/**
 * FixLongDescriptions.php
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
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorrectsLongDescriptions extends Command
{
    use ShowsFriendlyMessages;

    private const int MAX_LENGTH = 1000;
    protected $description       = 'Fixes long descriptions in journals and groups.';
    protected $signature         = 'correction:long-descriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $journals = TransactionJournal::where(DB::raw('LENGTH(description)'),'>', self::MAX_LENGTH)->get(['id', 'description']);
        $count    = 0;

        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            if (strlen($journal->description) > self::MAX_LENGTH) {
                $journal->description = substr($journal->description, 0, self::MAX_LENGTH);
                $journal->save();
                $this->friendlyWarning(sprintf('Truncated description of transaction journal #%d', $journal->id));
                ++$count;
            }
        }

        $groups   = TransactionGroup::where(DB::raw('LENGTH(title)'),'>', self::MAX_LENGTH)->get(['id', 'title']);

        /** @var TransactionGroup $group */
        foreach ($groups as $group) {
            if (strlen((string) $group->title) > self::MAX_LENGTH) {
                $group->title = substr($group->title, 0, self::MAX_LENGTH);
                $group->save();
                $this->friendlyWarning(sprintf('Truncated description of transaction group #%d', $group->id));
                ++$count;
            }
        }

        return 0;
    }
}
