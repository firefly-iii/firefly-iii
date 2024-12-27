<?php

/**
 * MigrateJournalNotes.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Console\Command;

class UpgradesJournalNotes extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_migrate_notes';

    protected $description          = 'Migrate notes for transaction journals.';

    protected $signature            = 'upgrade:480-notes {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $start = microtime(true);

        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        $count = 0;
        $set   = TransactionJournalMeta::whereName('notes')->get();

        /** @var TransactionJournalMeta $meta */
        foreach ($set as $meta) {
            $journal    = $meta->transactionJournal;
            $note       = $journal->notes()->first();
            if (null === $note) {
                $note = new Note();
                $note->noteable()->associate($journal);
            }

            $note->text = $meta->data;
            $note->save();
            app('log')->debug(sprintf('Migrated meta note #%d to Note #%d', $meta->id, $note->id));
            $meta->delete();

            ++$count;
        }

        if (0 === $count) {
            $this->friendlyPositive('No notes to migrate.');
        }
        if (0 !== $count) {
            $this->friendlyInfo(sprintf('Migrated %d note(s).', $count));
        }

        $end   = round(microtime(true) - $start, 2);
        $this->friendlyInfo(sprintf('Migrated notes in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
