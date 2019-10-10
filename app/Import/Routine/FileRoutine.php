<?php
/**
 * FileRoutine.php
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
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\FileProcessorInterface;
use Log;

/**
 * Class FileRoutine
 */
class FileRoutine implements RoutineInterface
{
    /** @var ImportJob The import job */
    private $importJob;
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;

    /**
     * At the end of each run(), the import routine must set the job to the expected status.
     *
     * The final status of the routine must be "provider_finished".
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug(sprintf('Now in run() for file routine with status: %s', $this->importJob->status));
        if ('ready_to_run' === $this->importJob->status) {
            $this->repository->setStatus($this->importJob, 'running');
            // get processor, depending on file type
            // is just CSV for now.
            $processor = $this->getProcessor();
            $processor->setImportJob($this->importJob);
            $transactions = $processor->run();

            $this->repository->setStatus($this->importJob, 'provider_finished');
            $this->repository->setStage($this->importJob, 'final');
            $this->repository->setTransactions($this->importJob, $transactions);

            return;
        }
        throw new FireflyException(sprintf('Import routine cannot handle status "%s"', $this->importJob->status)); // @codeCoverageIgnore
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }

    /**
     * Return the appropriate file routine handler for
     * the file type of the job.
     *
     * @return FileProcessorInterface
     */
    private function getProcessor(): FileProcessorInterface
    {
        $config = $this->repository->getConfiguration($this->importJob);
        $type   = $config['file-type'] ?? 'csv';
        $class  = config(sprintf('import.options.file.processors.%s', $type));

        return app($class);
    }
}
