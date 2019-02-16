<?php
/**
 * AttachmentCollector.php
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

namespace FireflyIII\Export\Collector;

use Carbon\Carbon;
use Crypt;
use FireflyIII\Models\Attachment;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Log;
use Storage;

/**
 * Class AttachmentCollector.
 *
 * @deprecated
 * @codeCoverageIgnore
 */
class AttachmentCollector extends BasicCollector implements CollectorInterface
{
    /** @var Carbon The end date of the range. */
    private $end;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem File system */
    private $exportDisk;
    /** @var AttachmentRepositoryInterface Attachment repository */
    private $repository;
    /** @var Carbon Start date of range */
    private $start;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem Disk with uploads on it */
    private $uploadDisk;

    /**
     * AttachmentCollector constructor.
     */
    public function __construct()
    {
        /** @var AttachmentRepositoryInterface repository */
        $this->repository = app(AttachmentRepositoryInterface::class);
        // make storage:
        $this->uploadDisk = Storage::disk('upload');
        $this->exportDisk = Storage::disk('export');

        parent::__construct();
    }

    /**
     * Run the routine.
     *
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
     * Set the start and end date.
     *
     * @param Carbon $start
     * @param Carbon $end
     */
    public function setDates(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end   = $end;
    }

    /** @noinspection MultipleReturnStatementsInspection */
    /**
     * Export attachments.
     *
     * @param Attachment $attachment
     *
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function exportAttachment(Attachment $attachment): bool
    {
        $file      = $attachment->fileName();
        $decrypted = false;
        if ($this->uploadDisk->exists($file)) {
            try {
                $decrypted = Crypt::decrypt($this->uploadDisk->get($file));
            } catch (DecryptException|FileNotFoundException $e) {
                Log::error('Catchable error: could not decrypt attachment #' . $attachment->id . ' because: ' . $e->getMessage());

                return false;
            }
        }
        if (false === $decrypted) {
            return false;
        }
        $exportFile = $this->exportFileName($attachment);
        $this->exportDisk->put($exportFile, $decrypted);
        $this->getEntries()->push($exportFile);

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
        return sprintf('%s-Attachment nr. %s - %s', $this->job->key, (string)$attachment->id, $attachment->filename);
    }

    /**
     * Get the attachments.
     *
     * @return Collection
     */
    private function getAttachments(): Collection
    {
        $this->repository->setUser($this->user);

        return $this->repository->getBetween($this->start, $this->end);
    }
}
