<?php
declare(strict_types = 1);
/**
 * AttachmentCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Collector;

use Amount;
use Crypt;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Log;
use Storage;

/**
 * Class AttachmentCollector
 *
 * @package FireflyIII\Export\Collector
 */
class AttachmentCollector extends BasicCollector implements CollectorInterface
{
    /** @var string */
    private $explanationString = '';
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $exportDisk;
    /** @var  AttachmentRepositoryInterface */
    private $repository;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $uploadDisk;

    /**
     * AttachmentCollector constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        $this->repository = app('FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface');
        // make storage:
        $this->uploadDisk = Storage::disk('upload');
        $this->exportDisk = Storage::disk('export');

        parent::__construct($job);
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        // grab all the users attachments:
        $attachments = $this->getAttachments();

        /** @var Attachment $attachment */
        foreach ($attachments as $attachment) {
            $this->exportAttachment($attachment);
        }

        // put the explanation string in a file and attach it as well.
        $file = $this->job->key . '-Source of all your attachments explained.txt';
        $this->exportDisk->put($file, $this->explanationString);
        Log::debug('Also put explanation file "' . $file . '" in the zip.');
        $this->getFiles()->push($file);
        return true;
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

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    private function exportAttachment(Attachment $attachment): bool
    {
        $file = $attachment->fileName();
        Log::debug('Original file is at "' . $file . '".');
        if ($this->uploadDisk->exists($file)) {
            try {
                $decrypted  = Crypt::decrypt($this->uploadDisk->get($file));
                $exportFile = $this->exportFileName($attachment);
                $this->exportDisk->put($exportFile, $decrypted);
                $this->getFiles()->push($exportFile);
                Log::debug('Stored file content in new file "' . $exportFile . '", which will be in the final zip file.');

                // explain:
                $this->explain($attachment);
            } catch (DecryptException $e) {
                Log::error('Catchable error: could not decrypt attachment #' . $attachment->id . ' because: ' . $e->getMessage());
            }

        }

        return true;
    }

    /**
     * Returns the new file name for the export file.
     *
     * @param $attachment
     *
     * @return string
     */
    private function exportFileName($attachment): string
    {

        return sprintf('%s-Attachment nr. %s - %s', $this->job->key, strval($attachment->id), $attachment->filename);
    }

    /**
     * @return Collection
     */
    private function getAttachments(): Collection
    {
        $attachments = $this->repository->get();

        Log::debug('Found ' . $attachments->count() . ' attachments.');

        return $attachments;
    }
}
