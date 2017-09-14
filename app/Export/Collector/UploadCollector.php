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
use Illuminate\Contracts\Filesystem\FileNotFoundException;
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
        $set = $this->job->user->importJobs()->whereIn('status', ['import_complete', 'finished'])->get(['import_jobs.*']);
        Log::debug(sprintf('Found %d import jobs', $set->count()));
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
        } catch (FileNotFoundException | DecryptException $e) {
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

}
