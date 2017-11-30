<?php
/**
 * AttachmentCollector.php
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

namespace FireflyIII\Export\Collector;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Log;
use Storage;

/**
 * Class AttachmentCollector.
 */
class AttachmentCollector extends BasicCollector implements CollectorInterface
{
    /** @var Carbon */
    private $end;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $exportDisk;
    /** @var AttachmentRepositoryInterface */
    private $repository;
    /** @var Carbon */
    private $start;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $uploadDisk;

    /**
     * AttachmentCollector constructor.
     */
    public function __construct()
    {
        // @var AttachmentRepositoryInterface repository
        $this->repository = app(AttachmentRepositoryInterface::class);
        // make storage:
        $this->uploadDisk = Storage::disk('upload');
        $this->exportDisk = Storage::disk('export');

        parent::__construct();
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
        $this->repository->setUser($this->user);
        $attachments = $this->repository->getBetween($this->start, $this->end);

        return $attachments;
    }
}
