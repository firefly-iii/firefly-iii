<?php
/**
 * EncryptFile.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Services\Internal\File\EncryptService;
use Illuminate\Console\Command;

/**
 * Class EncryptFile.
 *
 * @codeCoverageIgnore
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
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function handle(): int
    {
        $code = 0;
        $file = (string)$this->argument('file');
        $key  = (string)$this->argument('key');
        /** @var EncryptService $service */
        $service = app(EncryptService::class);

        try {
            $service->encrypt($file, $key);
        } catch (FireflyException $e) {
            $this->error($e->getMessage());
            $code = 1;
        }

        return $code;
    }
}
