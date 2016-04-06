<?php
declare(strict_types = 1);
/**
 * CsvExporter.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Exporter;

use FireflyIII\Export\Entry;
use FireflyIII\Models\ExportJob;
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
     *
     * @param ExportJob $job
     */
    public function __construct(ExportJob $job)
    {
        parent::__construct($job);

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

        // create CSV writer:
        $writer = Writer::createFromPath(new SplFileObject($fullPath, 'a+'), 'w');

        // all rows:
        $rows = [];

        // add header:
        $first  = $this->getEntries()->first();
        $rows[] = array_keys(get_object_vars($first));

        // then the rest:
        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            $rows[] = array_values(get_object_vars($entry));

        }
        $writer->insertAll($rows);
        return true;
    }

    private function tempFile()
    {
        $this->fileName = $this->job->key . '-records.csv';
    }
}
