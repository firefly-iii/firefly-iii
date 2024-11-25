<?php

/*
 * UpgradeFireflyInstructions.php
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

use FireflyIII\Support\System\GeneratesInstallationId;
use Illuminate\Console\Command;

/**
 * Class UpgradeFireflyInstructions.
 */
class UpgradeFireflyInstructions extends Command
{
    use GeneratesInstallationId;

    protected $description = 'Instructions in case of upgrade trouble.';

    protected $signature   = 'firefly:instructions {task}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->generateInstallationId();
        if ('update' === $this->argument('task')) {
            $this->updateInstructions();
        }
        if ('install' === $this->argument('task')) {
            $this->installInstructions();
        }

        return 0;
    }

    /**
     * Render upgrade instructions.
     */
    private function updateInstructions(): void
    {
        $version = (string)config('firefly.version');

        /** @var array $config */
        $config  = config('upgrade.text.upgrade');
        $text    = '';

        /** @var string $compare */
        foreach (array_keys($config) as $compare) {
            // if string starts with:
            if (str_starts_with($version, $compare)) {
                $text = (string)$config[$compare];
            }
        }

        // validate some settings.
        if ('' === $text && 'local' === (string)config('app.env')) {
            $text = 'Please set APP_ENV=production for a safer environment.';
        }

        $prefix  = 'v';
        if (str_starts_with($version, 'develop')) {
            $prefix = '';
        }

        $this->newLine();
        $this->showLogo();
        $this->newLine();
        $this->showLine();

        $this->boxed('');
        if ('' === $text) {
            $this->boxed(sprintf('Thank you for updating to Firefly III, %s%s', $prefix, $version));
            $this->boxedInfo('There are no extra upgrade instructions.');
            $this->boxed('Firefly III should be ready for use.');
            $this->boxed('');
            $this->showLine();

            return;
        }

        $this->boxed(sprintf('Thank you for updating to Firefly III, %s%s!', $prefix, $version));
        $this->boxedInfo($text);
        $this->boxed('');
        $this->showLine();
    }

    /**
     * The logo takes up 8 lines of code. So 8 colors can be used.
     */
    private function showLogo(): void
    {
        $today  = date('m-d');
        $month  = date('m');
        // variation in colors and effects just because I can!
        // default is Ukraine flag:
        $colors = ['blue', 'blue', 'blue', 'yellow', 'yellow', 'yellow', 'default', 'default'];

        // 5th of May is Dutch liberation day and 29th of April is Dutch King's Day and September 17 is my birthday.
        if ('05-01' === $today || '04-29' === $today || '09-17' === $today) {
            $colors = ['red', 'red', 'red', 'white', 'white', 'blue', 'blue', 'blue'];
        }

        // National Coming Out Day, International Day Against Homophobia, Biphobia and Transphobia and Pride Month
        if ('10-11' === $today || '05-17' === $today || '06' === $month) {
            $colors = ['red', 'bright-red', 'yellow', 'green', 'blue', 'magenta', 'default', 'default'];
        }

        // International Transgender Day of Visibility
        if ('03-31' === $today) {
            $colors = ['bright-blue', 'bright-red', 'white', 'white', 'bright-red', 'bright-blue', 'default', 'default'];
        }

        $this->line(sprintf('<fg=%s>              ______ _           __ _            _____ _____ _____  </>', $colors[0]));
        $this->line(sprintf('<fg=%s>             |  ____(_)         / _| |          |_   _|_   _|_   _| </>', $colors[1]));
        $this->line(sprintf('<fg=%s>             | |__   _ _ __ ___| |_| |_   _       | |   | |   | |   </>', $colors[2]));
        $this->line(sprintf('<fg=%s>             |  __| | | \'__/ _ \  _| | | | |      | |   | |   | |   </>', $colors[3]));
        $this->line(sprintf('<fg=%s>             | |    | | | |  __/ | | | |_| |     _| |_ _| |_ _| |_  </>', $colors[4]));
        $this->line(sprintf('<fg=%s>             |_|    |_|_|  \___|_| |_|\__, |    |_____|_____|_____| </>', $colors[5]));
        $this->line(sprintf('<fg=%s>                                       __/ |                        </>', $colors[6]));
        $this->line(sprintf('<fg=%s>                                      |___/                         </>', $colors[7]));
    }

    /**
     * Show a line.
     */
    private function showLine(): void
    {
        $line = '+';
        $line .= str_repeat('-', 78);
        $line .= '+';
        $this->line($line);
    }

    /**
     * Show a nice box.
     */
    private function boxed(string $text): void
    {
        $parts = explode("\n", wordwrap($text));
        foreach ($parts as $string) {
            $this->line('| '.sprintf('%-77s', $string).'|');
        }
    }

    /**
     * Show a nice info box.
     */
    private function boxedInfo(string $text): void
    {
        $parts = explode("\n", wordwrap($text));
        foreach ($parts as $string) {
            $this->info('| '.sprintf('%-77s', $string).'|');
        }
    }

    /**
     * Render instructions.
     */
    private function installInstructions(): void
    {
        $version = (string)config('firefly.version');

        /** @var array $config */
        $config  = config('upgrade.text.install');
        $text    = '';

        /** @var string $compare */
        foreach (array_keys($config) as $compare) {
            // if string starts with:
            if (str_starts_with($version, $compare)) {
                $text = (string)$config[$compare];
            }
        }

        // validate some settings.
        if ('' === $text && 'local' === (string)config('app.env')) {
            $text = 'Please set APP_ENV=production for a safer environment.';
        }

        $prefix  = 'v';
        if (str_starts_with($version, 'develop')) {
            $prefix = '';
        }

        $this->newLine();
        $this->showLogo();
        $this->newLine();
        $this->showLine();
        $this->boxed('');
        if ('' === $text) {
            $this->boxed(sprintf('Thank you for installing Firefly III, %s%s!', $prefix, $version));
            $this->boxedInfo('There are no extra installation instructions.');
            $this->boxed('Firefly III should be ready for use.');
            $this->boxed('');
            $this->showLine();

            return;
        }

        $this->boxed(sprintf('Thank you for installing Firefly III, %s%s!', $prefix, $version));
        $this->boxedInfo($text);
        $this->boxed('');
        $this->showLine();
    }
}
