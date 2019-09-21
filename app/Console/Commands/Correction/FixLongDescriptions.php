<?php
/**
 * FixLongDescriptions.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;

/**
 * Class FixLongDescriptions
 */
class FixLongDescriptions extends Command
{
    private const MAX_LENGTH = 1000;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes long descriptions in journals and groups.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:fix-long-descriptions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $journals = TransactionJournal::get(['id', 'description']);
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            if (strlen($journal->description) > self::MAX_LENGTH) {
                $journal->description = substr($journal->description, 0, self::MAX_LENGTH);
                $journal->save();
                $this->line(sprintf('Truncated description of transaction journal #%d', $journal->id));
            }
        }

        $groups = TransactionGroup::get(['id', 'title']);
        /** @var TransactionGroup $group */
        foreach ($groups as $group) {
            if (strlen($group->title) > self::MAX_LENGTH) {
                $group->title = substr($group->title, 0, self::MAX_LENGTH);
                $group->save();
                $this->line(sprintf('Truncated description of transaction group #%d', $group->id));
            }
        }

        return 0;
    }
}
