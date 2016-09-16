<?php
/**
 * UploadCollector.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Export\Collector;

use Crypt;
use FireflyIII\Models\ExportJob;
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
    /** @var string */
    private $expected;
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $exportDisk;
    private $importKeys = [];
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $uploadDisk;

    /**
     *
     * AttachmentCollector constructor.
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        parent::__construct($job);

        Log::debug('Going to collect attachments', ['key' => $job->key]);

        // make storage:
        $this->uploadDisk = Storage::disk('upload');
        $this->exportDisk = Storage::disk('export');

        // file names associated with the old import routine.
        $this->expected = 'csv-upload-' . auth()->user()->id . '-';

        // for the new import routine:
        $this->getImportKeys();
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        // grab upload directory.
        $files = $this->uploadDisk->files();

        foreach ($files as $entry) {
            $this->processUpload($entry);
        }

        return true;
    }

    /**
     *
     */
    private function getImportKeys()
    {
        $set = auth()->user()->importJobs()->where('status', 'import_complete')->get(['import_jobs.*']);
        if ($set->count() > 0) {
            $keys             = $set->pluck('key')->toArray();
            $this->importKeys = $keys;

        }
        Log::debug('Valid import keys are ', $this->importKeys);
    }

    /**
     * @param string $entry
     *
     * @return string
     */
    private function getOriginalUploadDate(string $entry): string
    {
        // this is an original upload.
        $parts          = explode('-', str_replace(['.csv.encrypted', $this->expected], '', $entry));
        $originalUpload = intval($parts[1]);
        $date           = date('Y-m-d \a\t H-i-s', $originalUpload);

        return $date;
    }

    /**
     * @param string $entry
     *
     * @return bool
     */
    private function isImportFile(string $entry): bool
    {
        $name = str_replace('.upload', '', $entry);
        if (in_array($name, $this->importKeys)) {
            Log::debug(sprintf('Import file "%s" is in array', $name), $this->importKeys);

            return true;
        }
        Log::debug(sprintf('Import file "%s" is NOT in array', $name), $this->importKeys);

        return false;
    }

    /**
     * @param string $entry
     *
     * @return bool
     */
    private function isOldImport(string $entry): bool
    {
        $len = strlen($this->expected);
        // file is part of the old import routine:
        if (substr($entry, 0, $len) === $this->expected) {

            return true;
        }

        return false;
    }

    /**
     * @param $entry
     */
    private function processUpload(string $entry)
    {
        // file is old import:
        if ($this->isOldImport($entry)) {
            $this->saveOldImportFile($entry);
        }

        // file is current import.
        if ($this->isImportFile($entry)) {
            $this->saveImportFile($entry);
        }
    }

    /**
     * @param string $entry
     */
    private function saveImportFile(string $entry)
    {
        // find job associated with import file:
        $name    = str_replace('.upload', '', $entry);
        $job     = auth()->user()->importJobs()->where('key', $name)->first();
        $content = '';
        try {
            $content = Crypt::decrypt($this->uploadDisk->get($entry));
        } catch (DecryptException $e) {
            Log::error('Could not decrypt old import file ' . $entry . '. Skipped because ' . $e->getMessage());
        }

        if (!is_null($job) && strlen($content) > 0) {
            // add to export disk.
            $date = $job->created_at->format('Y-m-d');
            $file = sprintf('%s-Old %s import dated %s.%s', $this->job->key, strtoupper($job->file_type), $date, $job->file_type);
            $this->exportDisk->put($file, $content);
            $this->getFiles()->push($file);
        }
    }

    /**
     * @param string $entry
     */
    private function saveOldImportFile(string $entry)
    {
        $content = '';
        try {
            $content = Crypt::decrypt($this->uploadDisk->get($entry));
        } catch (DecryptException $e) {
            Log::error('Could not decrypt old CSV import file ' . $entry . '. Skipped because ' . $e->getMessage());
        }

        if (strlen($content) > 0) {
            // add to export disk.
            $date = $this->getOriginalUploadDate($entry);
            $file = $this->job->key . '-Old import dated ' . $date . '.csv';
            $this->exportDisk->put($file, $content);
            $this->getFiles()->push($file);
        }
    }


}
