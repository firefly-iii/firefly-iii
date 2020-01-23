<?php
/**
 * UpgradeFireflyInstructions.php
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

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class UpgradeFireflyInstructions.
 *
 * @codeCoverageIgnore
 */
class UpgradeFireflyInstructions extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instructions in case of upgrade trouble.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:instructions {task}';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ('update' === (string)$this->argument('task')) {
            $this->updateInstructions();
        }
        if ('install' === (string)$this->argument('task')) {
            $this->installInstructions();
        }

        return 0;
    }

    /**
     * Show a nice box.
     *
     * @param string $text
     */
    private function boxed(string $text): void
    {
        $parts = explode("\n", wordwrap($text));
        foreach ($parts as $string) {
            $this->line('| ' . sprintf('%-77s', $string) . '|');
        }
    }

    /**
     * Show a nice info box.
     *
     * @param string $text
     */
    private function boxedInfo(string $text): void
    {
        $parts = explode("\n", wordwrap($text));
        foreach ($parts as $string) {
            $this->info('| ' . sprintf('%-77s', $string) . '|');
        }
    }

    /**
     * Render instructions.
     */
    private function installInstructions(): void
    {
        /** @var string $version */
        $version = config('firefly.version');
        $config  = config('upgrade.text.install');
        $text    = '';
        foreach (array_keys($config) as $compare) {
            // if string starts with:
            if (0 === strpos($version, $compare)) {
                $text = $config[$compare];
            }
        }
        $this->showLine();
        $this->boxed('');
        if (null === $text) {
            $this->boxed(sprintf('Thank you for installing Firefly III, v%s!', $version));
            $this->boxedInfo('There are no extra installation instructions.');
            $this->boxed('Firefly III should be ready for use.');
            $this->boxed('');
            $this->showLine();

            return;
        }

        $this->boxed(sprintf('Thank you for installing Firefly III, v%s!', $version));
        $this->boxedInfo($text);
        $this->boxed('');
        $this->showLine();
    }

    /**
     * Show a line.
     */
    private function showLine(): void
    {
        $line = '+';
        for ($i = 0; $i < 78; ++$i) {
            $line .= '-';
        }
        $line .= '+';
        $this->line($line);
    }

    /**
     * Render upgrade instructions.
     */
    private function updateInstructions(): void
    {
        /** @var string $version */
        $version = config('firefly.version');
        $config  = config('upgrade.text.upgrade');
        $text    = '';
        foreach (array_keys($config) as $compare) {
            // if string starts with:
            if (0 === strpos($version, $compare)) {
                $text = $config[$compare];
            }
        }
        $this->showLine();
        $this->boxed('');
        if (null === $text) {
            $this->boxed(sprintf('Thank you for updating to Firefly III, v%s', $version));
            $this->boxedInfo('There are no extra upgrade instructions.');
            $this->boxed('Firefly III should be ready for use.');
            $this->boxed('');
            $this->showLine();

            return;
        }

        $this->boxed(sprintf('Thank you for updating to Firefly III, v%s!', $version));
        $this->boxedInfo($text);
        $this->boxed('');
        $this->showLine();
    }
}
