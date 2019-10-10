<?php
/**
 * RoutineInterface.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Import\Routine;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;

/**
 * Interface RoutineInterface
 */
interface RoutineInterface
{
    /**
     * At the end of each run(), the import routine must set the job to the expected status.
     *
     * The final status of the routine must be "provider_finished".
     *
     * @throws FireflyException
     */
    public function run(): void;

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function setImportJob(ImportJob $importJob): void;
}
