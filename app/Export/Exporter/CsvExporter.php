<?php
/**
 * CsvExporter.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Export\Exporter;

use FireflyIII\Export\Entry\Entry;
use League\Csv\Writer;
use SplFileObject;

/**
 * Class CsvExporter
 *
 * @package FireflyIII\Export\Exporter
 */
class CsvExporter extends BasicExporter implements ExporterInterface
{
    /** @var  string */
    private $fileName;

    /**
     * CsvExporter constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        // create temporary file:
        $this->tempFile();

        // necessary for CSV writer:
        $fullPath = storage_path('export') . DIRECTORY_SEPARATOR . $this->fileName;
        $writer   = Writer::createFromPath(new SplFileObject($fullPath, 'a+'), 'w');
        $rows     = [];

        // get field names for header row:
        $first   = $this->getEntries()->first();
        $headers = array_keys(get_object_vars($first));
        $rows[]  = $headers;

        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $entry->$header;
            }
            $rows[] = $line;
        }
        $writer->insertAll($rows);

        return true;
    }


    private function tempFile()
    {
        $this->fileName = $this->job->key . '-records.csv';
    }
}
