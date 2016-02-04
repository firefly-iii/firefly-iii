<?php
/**
 * AttachmentCollector.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Collector;

use Auth;
use Crypt;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ExportJob;
use Log;

/**
 * Class AttachmentCollector
 *
 * @package FireflyIII\Export\Collector
 */
class AttachmentCollector extends BasicCollector implements CollectorInterface
{
    /**
     * AttachmentCollector constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        parent::__construct($job);
    }

    public function run()
    {
        // grab all the users attachments:
        $attachments = Auth::user()->attachments()->get();

        Log::debug('Found ' . $attachments->count() . ' attachments.');

        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $originalFile = storage_path('upload') . DIRECTORY_SEPARATOR . 'at-' . $attachment->id . '.data';
            if (file_exists($originalFile)) {
                Log::debug('Stored 1 attachment');
                $decrypted = Crypt::decrypt(file_get_contents($originalFile));
                $newFile   = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '-Attachment nr. ' . $attachment->id . ' - '
                             . $attachment->filename;
                file_put_contents($newFile, $decrypted);
                $this->getFiles()->push($newFile);
            }
        }
    }
}