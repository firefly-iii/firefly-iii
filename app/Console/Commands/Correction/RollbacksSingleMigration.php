<?php

declare(strict_types=1);

/*
 * RollbackSingleMigration.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollbacksSingleMigration extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'correction:rollback-single-migration {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes the last entry from the migration table. ';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $entry = DB::table('migrations')->orderBy('id', 'DESC')->first();

        if (null === $entry) {
            $this->friendlyError('There are no more database migrations to rollback.');

            return Command::FAILURE;
        }

        $this->friendlyLine(sprintf('This command will remove the database migration entry called "%s" from your database.', $entry->migration));
        $this->friendlyLine('This does not change the database in anyway. It makes Firefly III forget it made the changes in this particular migration.');
        $this->friendlyLine('');
        $this->friendlyLine('If you run "php artisan migrate" after doing this, Firefly III will try to run the database migration again.');
        $this->friendlyLine('Missing tables or indices will be created.');
        $this->friendlyLine('This may not work, or give you warnings, but if you have a botched database it may restore it again.');
        $this->friendlyLine('');
        $this->friendlyLine('If this doesn\'t work, run the command a few times to remove more rows and try "php artisan migrate" again.');
        $this->friendlyLine('');
        $res   = true;
        if (!$this->option('force')) {
            $this->friendlyWarning('Use this command at your own risk.');
            $res = $this->confirm('Are you sure you want to continue?');
        }

        if ($res) {
            DB::table('migrations')->where('id', (int) $entry->id)->delete();
            $this->friendlyInfo(sprintf('Database migration #%d ("%s") is deleted.', $entry->id, $entry->migration));
            $this->friendlyLine('');
            $this->friendlyLine('Try running "php artisan migrate" now.');
            $this->friendlyLine('');
        }
        if (!$res) {
            $this->friendlyError('User cancelled, will not delete anything.');
        }

        return Command::SUCCESS;
    }
}
