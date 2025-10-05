<?php


/*
 * ResetsErrorMailLimit.php
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

declare(strict_types=1);

namespace FireflyIII\Console\Commands\System;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

use function Safe\file_put_contents;
use function Safe\json_encode;

class ResetsErrorMailLimit extends Command
{
    use ShowsFriendlyMessages;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'firefly-iii:reset-error-mail-limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the number of error mails sent.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file      = storage_path('framework/cache/error-count.json');
        $directory = storage_path('framework/cache');
        $limits    = [];

        if (!is_writable($directory)) {
            $this->friendlyError(sprintf('Cannot write to directory "%s", cannot rate limit errors.', $directory));

            return CommandAlias::FAILURE;
        }
        if (!file_exists($file)) {
            $this->friendlyInfo(sprintf('Created new limits file at "%s"', $file));
            file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));

            return CommandAlias::SUCCESS;
        }
        if (!is_writable($file)) {
            $this->friendlyError(sprintf('Cannot write to "%s", cannot rate limit errors.', $file));

            return CommandAlias::FAILURE;
        }

        $this->friendlyInfo(sprintf('Successfully reset the error rate-limits file located at "%s"', $file));
        file_put_contents($file, json_encode($limits, JSON_PRETTY_PRINT));

        return CommandAlias::SUCCESS;
    }
}
