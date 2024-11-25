<?php

/*
 * ForceMigration.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Console\Commands\VerifiesAccessToken;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Class ForceMigration
 */
class ForceMigration extends Command
{
    use ShowsFriendlyMessages;
    use VerifiesAccessToken;

    protected $description = 'This command will force-run all database migrations.';

    protected $signature   = 'firefly-iii:force-migrations
                            {--user=1 : The user ID.}
                            {--token= : The user\'s access token.}';

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        if (!$this->verifyAccessToken()) {
            $this->friendlyError('Invalid access token.');

            return 1;
        }

        $this->friendlyError('Running this command is dangerous and can cause data loss.');
        $this->friendlyError('Please do not continue.');
        $question = $this->confirm('Do you want to continue?');
        if (true === $question) {
            $user = $this->getUser();
            Log::channel('audit')->info(sprintf('User #%d ("%s") forced migrations.', $user->id, $user->email));
            $this->forceMigration();

            return 0;
        }

        return 0;
    }

    private function forceMigration(): void
    {
        DB::commit();
        $this->friendlyLine('Dropping "migrations" table...');
        sleep(2);
        Schema::dropIfExists('migrations');
        $this->friendlyLine('Re-run all migrations...');
        Artisan::call('migrate', ['--seed' => true, '--force' => true]);
        sleep(2);
        $this->friendlyLine('');
        $this->friendlyWarning('There is a good chance you just saw a lot of error messages.');
        $this->friendlyWarning('No need to panic yet. First try to access Firefly III (again).');
        $this->friendlyWarning('The issue, whatever it was, may have been solved now.');
        $this->friendlyLine('');
    }
}
