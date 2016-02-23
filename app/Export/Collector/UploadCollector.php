<?php
declare(strict_types = 1);
/**
 * UploadCollector.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Collector;

use Auth;
use Crypt;
use FireflyIII\Models\ExportJob;
use Illuminate\Contracts\Encryption\DecryptException;
use Log;

/**
 * Class UploadCollector
 *
 * @package FireflyIII\Export\Collector
 */
class UploadCollector extends BasicCollector implements CollectorInterface
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

    /**
     *
     */
    public function run()
    {
        // grab upload directory.
        $path  = storage_path('upload');
        $files = scandir($path);
        Log::debug('Found ' . count($files) . ' in the upload directory.');
        // only allow old uploads for this user:
        $expected = 'csv-upload-' . Auth::user()->id . '-';
        Log::debug('Searching for files that start with: "' . $expected . '".');
        $len = strlen($expected);
        foreach ($files as $entry) {
            if (substr($entry, 0, $len) === $expected) {
                Log::debug($entry . ' is part of this users original uploads.');
                try {
                    // this is an original upload.
                    $parts          = explode('-', str_replace(['.csv.encrypted', $expected], '', $entry));
                    $originalUpload = intval($parts[1]);
                    $date           = date('Y-m-d \a\t H-i-s', $originalUpload);
                    $newFileName    = 'Old CSV import dated ' . $date . '.csv';
                    $content        = Crypt::decrypt(file_get_contents($path . DIRECTORY_SEPARATOR . $entry));
                    $fullPath       = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '-' . $newFileName;

                    Log::debug('Will put "' . $fullPath . '" in the zip file.');
                    // write to file:
                    file_put_contents($fullPath, $content);

                    // add entry to set:
                    $this->getFiles()->push($fullPath);
                } catch (DecryptException $e) {
                    Log::error('Could not decrypt old CSV import file ' . $entry . '. Skipped because ' . $e->getMessage());
                }
            } else {
                Log::debug($entry . ' is not part of this users original uploads.');
            }
        }
    }
}
