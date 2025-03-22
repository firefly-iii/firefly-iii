<?php

/*
 * ScanAttachments.php
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
use FireflyIII\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class ScansAttachments extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Rescan all attachments and re-set the correct MD5 hash and mime.';

    protected $signature   = 'firefly-iii:scan-attachments';

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
            $encryptedContent = $disk->get($fileName);
            if (null === $encryptedContent) {
                app('log')->error(sprintf('No content for attachment #%d under filename "%s"', $attachment->id, $fileName));

                continue;
            }

            try {
                $decryptedContent = Crypt::decrypt($encryptedContent); // verified
            } catch (DecryptException $e) {
                app('log')->error(sprintf('Could not decrypt data of attachment #%d: %s', $attachment->id, $e->getMessage()));
                $decryptedContent = $encryptedContent;
            }
            $tempFileName     = tempnam(sys_get_temp_dir(), 'FireflyIII');
            if (false === $tempFileName) {
                app('log')->error(sprintf('Could not create temporary file for attachment #%d', $attachment->id));

                exit(1);
            }
            file_put_contents($tempFileName, $decryptedContent);
            $attachment->md5  = (string) md5_file($tempFileName);
            $attachment->mime = (string) mime_content_type($tempFileName);
            $attachment->save();
            $this->friendlyInfo(sprintf('Fixed attachment #%d', $attachment->id));
        }

        return 0;
    }
}
