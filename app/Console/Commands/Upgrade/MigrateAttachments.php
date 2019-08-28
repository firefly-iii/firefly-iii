<?php
/**
 * MigrateAttachments.php
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


use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use Illuminate\Console\Command;
use Log;

/**
 * Class MigrateAttachments
 */
class MigrateAttachments extends Command
{
    public const CONFIG_NAME = '4780_migrate_attachments';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates attachment meta-data.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:migrate-attachments {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // @codeCoverageIgnoreStart
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        // @codeCoverageIgnoreEnd

        $attachments = Attachment::get();
        $count       = 0;

        /** @var Attachment $att */
        foreach ($attachments as $att) {

            // move description:
            $description = (string)$att->description;
            if ('' !== $description) {

                // find or create note:
                $note = $att->notes()->first();
                if (null === $note) {
                    $note = new Note;
                    $note->noteable()->associate($att);
                }
                $note->text = $description;
                $note->save();

                // clear description:
                $att->description = '';
                $att->save();

                Log::debug(sprintf('Migrated attachment #%s description to note #%d.', $att->id, $note->id));
                $count++;
            }
        }
        if (0 === $count) {
            $this->line('All attachments are OK.');
        }
        if (0 !== $count) {
            $this->line(sprintf('Updated %d attachment(s).', $count));
        }
        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Migrated attachment notes in %s seconds.', $end));
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
