<?php
/**
 * EncryptFile.php
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

use Crypt;
use Illuminate\Console\Command;

/**
 * Class EncryptFile
 *
 * @package FireflyIII\Console\Commands
 */
class EncryptFile extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypts a file and places it in the storage/upload directory.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:encrypt-file {file} {key}';

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
        $file = e(strval($this->argument('file')));
        if (!file_exists($file)) {
            $this->error(sprintf('File "%s" does not seem to exist.', $file));

            return;
        }
        $content = file_get_contents($file);
        $content = Crypt::encrypt($content);
        $newName = e(strval($this->argument('key'))) . '.upload';

        $path = storage_path('upload') . '/' . $newName;
        file_put_contents($path, $content);
        $this->line(sprintf('Encrypted "%s" and put it in "%s"', $file, $path));
    }
}
