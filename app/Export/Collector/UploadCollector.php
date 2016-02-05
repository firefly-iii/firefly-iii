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
        // only allow old uploads for this user:
        $expected = 'csv-upload-' . Auth::user()->id . '-';
        $len      = strlen($expected);
        foreach ($files as $entry) {
            if (substr($entry, 0, $len) === $expected) {
                // this is an original upload.
                $parts          = explode('-', str_replace(['.csv.encrypted', $expected], '', $entry));
                $originalUpload = intval($parts[1]);
                $date           = date('Y-m-d \a\t H-i-s', $originalUpload);
                $newFileName    = 'Old CSV import dated ' . $date . '.csv';
                $content        = Crypt::decrypt(file_get_contents($path . DIRECTORY_SEPARATOR . $entry));
                $fullPath       = storage_path('export') . DIRECTORY_SEPARATOR . $this->job->key . '-' . $newFileName;

                // write to file:
                file_put_contents($fullPath, $content);

                // add entry to set:
                $this->getFiles()->push($fullPath);
            }
        }
    }
}