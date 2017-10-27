<?php
/**
 * UpgradeFireflyInstructions.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class UpgradeFireflyInstructions
 *
 * @package FireflyIII\Console\Commands
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
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        if ($this->argument('task') === 'update') {
            $this->updateInstructions();
        }
        if ($this->argument('task') === 'install') {
            $this->installInstructions();
        }
    }

    /**
     * Show a nice box
     *
     * @param string $text
     */
    private function boxed(string $text)
    {
        $parts = explode("\n", wordwrap($text));
        foreach ($parts as $string) {
            $this->line('| ' . sprintf('%-77s', $string) . '|');
        }
    }

    /**
     * Show a nice info box
     *
     * @param string $text
     */
    private function boxedInfo(string $text)
    {
        $parts = explode("\n", wordwrap($text));
        foreach ($parts as $string) {
            $this->info('| ' . sprintf('%-77s', $string) . '|');
        }
    }

    /**
     * Render instructions.
     */
    private function installInstructions()
    {
        /** @var string $version */
        $version = config('firefly.version');
        $config  = config('upgrade.text.install');
        $text    = '';
        foreach (array_keys($config) as $compare) {
            // if string starts with:
            $len = strlen($compare);
            if (substr($version, 0, $len) === $compare) {
                $text = $config[$compare];
            }

        }
        $this->showLine();
        $this->boxed('');
        if (is_null($text)) {

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
     * Show a line
     */
    private function showLine()
    {
        $line = '+';
        for ($i = 0; $i < 78; $i++) {
            $line .= '-';
        }
        $line .= '+';
        $this->line($line);

    }

    /**
     * Render upgrade instructions.
     */
    private function updateInstructions()
    {
        /** @var string $version */
        $version = config('firefly.version');
        $config  = config('upgrade.text.upgrade');
        $text    = '';
        foreach (array_keys($config) as $compare) {
            // if string starts with:
            $len = strlen($compare);
            if (substr($version, 0, $len) === $compare) {
                $text = $config[$compare];
            }

        }
        $this->showLine();
        $this->boxed('');
        if (is_null($text)) {

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
