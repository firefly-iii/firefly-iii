<?php
/**
 * ScanAttachments.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

/** @noinspection PhpDynamicAsStaticMethodCallInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use Crypt;
use FireflyIII\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Log;
use Storage;

/**
 * Class ScanAttachments.
 *
 * @codeCoverageIgnore
 */
class ScanAttachments extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rescan all attachments and re-set the MD5 hash and mime.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:scan-attachments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $attachments = Attachment::get();
        $disk        = Storage::disk('upload');
        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $fileName         = $attachment->fileName();
            $decryptedContent = '';
            try {
                $encryptedContent = $disk->get($fileName);
            } catch (FileNotFoundException $e) {
                $this->error(sprintf('Could not find data for attachment #%d: %s', $attachment->id, $e->getMessage()));
                continue;
            }
            try {
                $decryptedContent = Crypt::decrypt($encryptedContent); // verified
            } catch (DecryptException $e) {
                Log::error(sprintf('Could not decrypt data of attachment #%d: %s', $attachment->id, $e->getMessage()));
                $decryptedContent = $encryptedContent;
            }
            $tempFileName = tempnam(sys_get_temp_dir(), 'FireflyIII');
            file_put_contents($tempFileName, $decryptedContent);
            $md5              = md5_file($tempFileName);
            $mime             = mime_content_type($tempFileName);
            $attachment->md5  = $md5;
            $attachment->mime = $mime;
            $attachment->save();
            $this->line(sprintf('Fixed attachment #%d', $attachment->id));
        }

        return 0;
    }
}
