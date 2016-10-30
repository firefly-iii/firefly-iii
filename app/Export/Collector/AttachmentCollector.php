<?php
/**
 * AttachmentCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Export\Collector;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\ExportJob;
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
    /** @var  Carbon */
    private $end;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $exportDisk;
    /** @var  AttachmentRepositoryInterface */
    private $repository;
    /** @var  Carbon */
    private $start;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $uploadDisk;

    /**
     * AttachmentCollector constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        /** @var AttachmentRepositoryInterface repository */
        $this->repository = app(AttachmentRepositoryInterface::class);
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

        return true;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     */
    public function setDates(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    private function exportAttachment(Attachment $attachment): bool
    {
        $file = $attachment->fileName();
        if ($this->uploadDisk->exists($file)) {
            try {
                $decrypted  = Crypt::decrypt($this->uploadDisk->get($file));
                $exportFile = $this->exportFileName($attachment);
                $this->exportDisk->put($exportFile, $decrypted);
                $this->getEntries()->push($exportFile);

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
        $attachments = $this->repository->getBetween($this->start, $this->end);

        return $attachments;
    }
}
