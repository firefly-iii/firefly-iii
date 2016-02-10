<?php
declare(strict_types = 1);
/**
 * AttachmentCollector.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Collector;

use Amount;
use Auth;
use Crypt;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Contracts\Encryption\DecryptException;
use Log;

/**
 * Class AttachmentCollector
 *
 * @package FireflyIII\Export\Collector
 */
class AttachmentCollector extends BasicCollector implements CollectorInterface
{
    /** @var string */
    private $explanationString = '';

    /**
     * AttachmentCollector constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        parent::__construct($job);
    }

    /**
     *
     */
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
                try {
                    $decrypted = Crypt::decrypt(file_get_contents($originalFile));
                    $newFile   = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '-Attachment nr. ' . $attachment->id . ' - '
                                 . $attachment->filename;
                    file_put_contents($newFile, $decrypted);
                    $this->getFiles()->push($newFile);

                    // explain:
                    $this->explain($attachment);
                } catch (DecryptException $e) {
                    Log::error('Catchable error: could not decrypt attachment #' . $attachment->id);
                }

            }
        }

        // put the explanation string in a file and attach it as well.
        $explanationFile = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '-Source of all your attachments explained.txt';
        file_put_contents($explanationFile, $this->explanationString);
        $this->getFiles()->push($explanationFile);
    }

    /**
     * @param Attachment $attachment
     */
    private function explain(Attachment $attachment)
    {
        /** @var TransactionJournal $journal */
        $journal = $attachment->attachable;
        $args    = [
            'attachment_name' => $attachment->filename,
            'attachment_id'   => $attachment->id,
            'type'            => strtolower($journal->transactionType->type),
            'description'     => $journal->description,
            'journal_id'      => $journal->id,
            'date'            => $journal->date->formatLocalized(strval(trans('config.month_and_day'))),
            'amount'          => Amount::formatJournal($journal, false),
        ];
        $string  = trans('firefly.attachment_explanation', $args) . "\n";
        $this->explanationString .= $string;

    }
}
