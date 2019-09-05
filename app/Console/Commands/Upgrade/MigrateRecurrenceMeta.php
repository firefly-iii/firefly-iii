<?php
/**
 * MigrateRecurrenceMeta.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Models\RecurrenceMeta;
use FireflyIII\Models\RecurrenceTransactionMeta;
use Illuminate\Console\Command;

/**
 * Class MigrateRecurrenceMeta
 */
class MigrateRecurrenceMeta extends Command
{
    public const CONFIG_NAME = '481_migrate_recurrence_meta';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate recurrence meta data';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:migrate-recurrence-meta {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        $count = $this->migrateMetaData();

        if (0 === $count) {
            $this->line('No recurrence meta data migrated.');
        }
        if ($count > 0) {
            $this->line(sprintf('Migrated %d meta data entries', $count));
        }

        $this->markAsExecuted();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Migrated recurrence meta data in %s seconds.', $end));

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

    /**
     * @param RecurrenceMeta $meta
     *
     * @return int
     */
    private function migrateEntry(RecurrenceMeta $meta): int
    {
        $recurrence       = $meta->recurrence;
        if (null === $recurrence) {
            return 0;
        }
        $firstTransaction = $recurrence->recurrenceTransactions()->first();
        if (null === $firstTransaction) {
            return 0;
        }
        $value = $meta->value;

        if ('tags' === $meta->name) {
            $array = explode(',', $meta->value);
            $value = json_encode($array);
        }

        RecurrenceTransactionMeta::create(
            [
                'rt_id' => $firstTransaction->id,
                'name'  => $meta->name,
                'value' => $value,
            ]
        );
        $meta->forceDelete();

        return 1;
    }

    /**
     * @return int
     */
    private function migrateMetaData(): int
    {
        $count = 0;
        // get all recurrence meta data:
        $collection = RecurrenceMeta::with('recurrence')->get();
        /** @var RecurrenceMeta $meta */
        foreach ($collection as $meta) {
            $count += $this->migrateEntry($meta);
        }

        return $count;
    }
}
