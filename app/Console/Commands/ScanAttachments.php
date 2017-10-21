<?php
/**
 * ScanAttachments.php
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
use FireflyIII\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Storage;

/**
 * Class ScanAttachments
 *
 * @package FireflyIII\Console\Commands
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
    protected $signature = 'firefly:scan-attachments';

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
        $attachments = Attachment::get();
        $disk        = Storage::disk('upload');
        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $fileName = $attachment->fileName();
            try {
                $content = $disk->get($fileName);
            } catch (FileNotFoundException $e) {
                $this->error(sprintf('Could not find data for attachment #%d', $attachment->id));
                continue;
            }
            try {
                $decrypted = Crypt::decrypt($content);
            } catch (DecryptException $e) {
                $this->error(sprintf('Could not decrypt data of attachment #%d', $attachment->id));
                continue;
            }
            $tmpfname = tempnam(sys_get_temp_dir(), 'FireflyIII');
            file_put_contents($tmpfname, $decrypted);
            $md5              = md5_file($tmpfname);
            $mime             = mime_content_type($tmpfname);
            $attachment->md5  = $md5;
            $attachment->mime = $mime;
            $attachment->save();
            $this->line(sprintf('Fixed attachment #%d', $attachment->id));
        }
    }
}
