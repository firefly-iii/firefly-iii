<?php

/**
 * MigrateAttachments.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Note;
use Illuminate\Console\Command;

/**
 * Class MigrateAttachments
 */
class MigrateAttachments extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '480_migrate_attachments';

    protected $description          = 'Migrates attachment meta-data.';

    protected $signature            = 'firefly-iii:migrate-attachments {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        $start       = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->friendlyInfo('This command has already been executed.');

            return 0;
        }

        $attachments = Attachment::get();
        $count       = 0;

        /** @var Attachment $att */
        foreach ($attachments as $att) {
            // move description:
            $attDescription = (string)$att->description;
            if ('' !== $attDescription) {
                // find or create note:
                $note             = $att->notes()->first();
                if (null === $note) {
                    $note = new Note();
                    $note->noteable()->associate($att);
                }
                $note->text       = $attDescription;
                $note->save();

                // clear description:
                $att->description = '';
                $att->save();

                app('log')->debug(sprintf('Migrated attachment #%s description to note #%d.', $att->id, $note->id));
                ++$count;
            }
        }
        if (0 === $count) {
            $this->friendlyPositive('All attachments are OK.');
        }
        if (0 !== $count) {
            $this->friendlyInfo(sprintf('Updated %d attachment(s).', $count));
        }
        $end         = round(microtime(true) - $start, 2);
        $this->friendlyInfo(sprintf('Migrated attachment notes in %s seconds.', $end));
        $this->markAsExecuted();

        return 0;
    }

    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool)$configVar->data;
        }

        return false;
    }

    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
