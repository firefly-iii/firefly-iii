<?php
/**
 * UploadCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Collector;

use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Log;
use Storage;

/**
 * Class UploadCollector
 *
 * @package FireflyIII\Export\Collector
 */
class UploadCollector extends BasicCollector implements CollectorInterface
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $exportDisk;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $uploadDisk;
    /** @var string */
    private $vintageFormat;

    /**
     * AttachmentCollector constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->uploadDisk = Storage::disk('upload');
        $this->exportDisk = Storage::disk('export');
    }

    /**
     * Is called from the outside to actually start the export.
     *
     * @return bool
     */
    public function run(): bool
    {
        Log::debug('Going to collect attachments', ['key' => $this->job->key]);

        // file names associated with the old import routine.
        $this->vintageFormat = sprintf('csv-upload-%d-', $this->job->user->id);

        // collect old upload files (names beginning with "csv-upload".
        $this->collectVintageUploads();

        // then collect current upload files:
        $this->collectModernUploads();

        return true;
    }

    /**
     * This method collects all the uploads that are uploaded using the new importer. So after the summer of 2016.
     *
     * @return bool
     */
    private function collectModernUploads(): bool
    {
        $set  = $this->job->user->importJobs()->where('status', 'import_complete')->get(['import_jobs.*']);
        $keys = [];
        if ($set->count() > 0) {
            $keys = $set->pluck('key')->toArray();
        }

        foreach ($keys as $key) {
            $this->processModernUpload($key);
        }

        return true;
    }

    /**
     * This method collects all the uploads that are uploaded using the "old" importer. So from before the summer of 2016.
     *
     * @return bool
     */
    private function collectVintageUploads(): bool
    {
        // grab upload directory.
        $files = $this->uploadDisk->files();

        foreach ($files as $entry) {
            $this->processVintageUpload($entry);
        }

        return true;
    }

    /**
     * This method tells you when the vintage upload file was actually uploaded.
     *
     * @param string $entry
     *
     * @return string
     */
    private function getVintageUploadDate(string $entry): string
    {
        // this is an original upload.
        $parts          = explode('-', str_replace(['.csv.encrypted', $this->vintageFormat], '', $entry));
        $originalUpload = intval($parts[1]);
        $date           = date('Y-m-d \a\t H-i-s', $originalUpload);

        return $date;
    }

    /**
     * Tells you if a file name is a vintage upload.
     *
     * @param string $entry
     *
     * @return bool
     */
    private function isVintageImport(string $entry): bool
    {
        $len = strlen($this->vintageFormat);
        // file is part of the old import routine:
        if (substr($entry, 0, $len) === $this->vintageFormat) {

            return true;
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private function processModernUpload(string $key): bool
    {
        // find job associated with import file:
        $job = $this->job->user->importJobs()->where('key', $key)->first();
        if (is_null($job)) {
            return false;
        }

        // find the file for this import:
        $content = '';
        try {
            $content = Crypt::decrypt($this->uploadDisk->get(sprintf('%s.upload', $key)));
        } catch (DecryptException $e) {
            Log::error(sprintf('Could not decrypt old import file "%s". Skipped because: %s', $key, $e->getMessage()));
        }

        if (strlen($content) > 0) {
            // add to export disk.
            $date = $job->created_at->format('Y-m-d');
            $file = sprintf('%s-Old %s import dated %s.%s', $this->job->key, strtoupper($job->file_type), $date, $job->file_type);
            $this->exportDisk->put($file, $content);
            $this->getEntries()->push($file);
        }

        return true;
    }

    /**
     * If the file is a vintage upload, process it.
     *
     * @param string $entry
     *
     * @return bool
     */
    private function processVintageUpload(string $entry): bool
    {
        if ($this->isVintageImport($entry)) {
            $this->saveVintageImportFile($entry);

            return true;
        }

        return false;
    }


    /**
     * This will store the content of the old vintage upload somewhere.
     *
     * @param string $entry
     */
    private function saveVintageImportFile(string $entry)
    {
        $content = '';
        try {
            $content = Crypt::decrypt($this->uploadDisk->get($entry));
        } catch (DecryptException $e) {
            Log::error('Could not decrypt old CSV import file ' . $entry . '. Skipped because ' . $e->getMessage());
        }

        if (strlen($content) > 0) {
            // add to export disk.
            $date = $this->getVintageUploadDate($entry);
            $file = $this->job->key . '-Old import dated ' . $date . '.csv';
            $this->exportDisk->put($file, $content);
            $this->getEntries()->push($file);
        }
    }


}
