<?php
/**
 * MigrateJournalNotes.php
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

declare(strict_types=1);

namespace FireflyIII\Console\Commands\Upgrade;


use Exception;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournalMeta;
use Illuminate\Console\Command;
use Log;

/**
 * Class MigrateJournalNotes
 */
class MigrateJournalNotes extends Command
{
    public const CONFIG_NAME = '4780_migrate_notes';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate notes for transaction journals.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:migrate-notes {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        // @codeCoverageIgnoreStart
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd
        $count = 0;
        /** @noinspection PhpUndefinedMethodInspection */
        $set = TransactionJournalMeta::whereName('notes')->get();
        /** @var TransactionJournalMeta $meta */
        foreach ($set as $meta) {
            $journal = $meta->transactionJournal;
            $note    = $journal->notes()->first();
            if (null === $note) {
                $note = new Note();
                $note->noteable()->associate($journal);
            }

            $note->text = $meta->data;
            $note->save();
            Log::debug(sprintf('Migrated meta note #%d to Note #%d', $meta->id, $note->id));
            try {
                $meta->delete();
                // @codeCoverageIgnoreStart
            } catch (Exception $e) {
                Log::error(sprintf('Could not delete old meta entry #%d: %s', $meta->id, $e->getMessage()));
            }
            // @codeCoverageIgnoreEnd
            $count++;
        }

        if (0 === $count) {
            $this->line('No notes to migrate.');
        }
        if (0 !== $count) {
            $this->line(sprintf('Migrated %d note(s).', $count));
        }

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Migrated notes in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    /**
     * @return bool
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false; // @codeCoverageIgnore
    }


    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}