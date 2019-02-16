<?php

/**
 * CsvExporter.php
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

namespace FireflyIII\Export\Exporter;

use FireflyIII\Export\Entry\Entry;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

/**
 * Class CsvExporter.
 *
 * @codeCoverageIgnore
 * @deprecated
 */
class CsvExporter extends BasicExporter implements ExporterInterface
{
    /** @var string Filename */
    private $fileName;

    /**
     * Get file name.
     *
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Run collector.
     *
     * @return bool
     *
     */
    public function run(): bool
    {
        // choose file name:
        $this->fileName = $this->job->key . '-records.csv';

        //we create the CSV into memory
        $writer = Writer::createFromString('');
        $rows   = [];

        // get field names for header row:
        $first   = $this->getEntries()->first();
        $headers = [];
        if (null !== $first) {
            $headers = array_keys(get_object_vars($first));
        }

        $rows[] = $headers;

        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $entry->$header;
            }
            $rows[] = $line;
        }
        $writer->insertAll($rows);
        $disk = Storage::disk('export');
        $disk->put($this->fileName, $writer->getContent());

        return true;
    }
}
