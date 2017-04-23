<?php
/**
 * EncryptFile.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
