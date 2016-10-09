<?php
/**
 * ScanAttachments.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

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

            // try to grab file content:
            try {
                $content = $disk->get($fileName);
            } catch (FileNotFoundException $e) {
                $this->error(sprintf('Could not find data for attachment #%d', $attachment->id));
                continue;
            }
            // try to decrypt content.
            try {
                $decrypted = Crypt::decrypt($content);
            } catch (DecryptException $e) {
                $this->error(sprintf('Could not decrypt data of attachment #%d', $attachment->id));
                continue;
            }

            // make temp file:
            $tmpfname = tempnam(sys_get_temp_dir(), 'FireflyIII');

            // store content in temp file:
            file_put_contents($tmpfname, $decrypted);

            // get md5 and mime
            $md5  = md5_file($tmpfname);
            $mime = mime_content_type($tmpfname);

            // update attachment:
            $attachment->md5  = $md5;
            $attachment->mime = $mime;
            $attachment->save();


            $this->line(sprintf('Fixed attachment #%d', $attachment->id));

            // find file:

        }
    }
}
