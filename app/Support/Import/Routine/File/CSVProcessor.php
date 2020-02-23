<?php
/**
 * CSVProcessor.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\File;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use Log;


/**
 * Class CSVProcessor
 *
 */
class CSVProcessor implements FileProcessorInterface
{
    /** @var ImportJob */
    private $importJob;

    /**
     * Fires the file processor.
     *
     * @return array
     * @throws FireflyException
     */
    public function run(): array
    {
        Log::debug('Now in CSVProcessor() run');

        // create separate objects to handle separate tasks:
        /** @var LineReader $lineReader */
        $lineReader = app(LineReader::class);
        $lineReader->setImportJob($this->importJob);
        $lines = $lineReader->getLines();

        // convert each line into a small set of "ColumnValue" objects,
        // joining each with its mapped counterpart.
        /** @var MappingConverger $mappingConverger */
        $mappingConverger = app(MappingConverger::class);
        $mappingConverger->setImportJob($this->importJob);
        $converged = $mappingConverger->converge($lines);

        // validate mapped values:
        /** @var MappedValuesValidator $validator */
        $validator = app(MappedValuesValidator::class);
        $validator->setImportJob($this->importJob);
        $mappedValues = $validator->validate($mappingConverger->getMappedValues());

        // make import transaction things from these objects.
        /** @var ImportableCreator $creator */
        $creator     = app(ImportableCreator::class);
        $importables = $creator->convertSets($converged);

        /** @var ImportableConverter $converter */
        $converter = app(ImportableConverter::class);
        $converter->setImportJob($this->importJob);
        $converter->setMappedValues($mappedValues);

        return $converter->convert($importables);
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        Log::debug('Now in setImportJob()');
        $this->importJob = $importJob;
    }
}
