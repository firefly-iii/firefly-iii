<?php

declare(strict_types=1);
/*
 * ValidatesFilePermissions.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;

class ValidatesFilePermissions extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'integrity:file-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directories = [storage_path('upload')];
        $errors      = false;

        /** @var string $directory */
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                $this->friendlyError(sprintf('Directory "%s" cannot found. It is necessary to allow files to be uploaded.', $directory));
                $errors = true;

                continue;
            }
            if (!is_writable($directory)) {
                $this->friendlyError(sprintf('Directory "%s" is not writeable. Uploading attachments may fail silently.', $directory));
                $errors = true;
            }
        }
        if (false === $errors) {
            $this->friendlyInfo('All necessary file paths seem to exist, and are writeable.');
        }

        return self::SUCCESS;
    }
}
