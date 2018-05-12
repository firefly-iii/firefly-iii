<?php
/**
 * FileRoutineInterface.php
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

namespace FireflyIII\Support\Import\Routine\File;
use FireflyIII\Models\ImportJob;


/**
 * Interface FileProcessorInterface
 *
 * @package FireflyIII\Support\Import\Routine\File
 */
interface FileProcessorInterface
{

    /**
     * Fires the file processor.
     *
     * @return array
     */
    public function run(): array;

    /**
     * Set values.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void;
}
