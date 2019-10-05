<?php
/**
 * FinTSRoutine.php
 * Copyright (c) 2019 https://github.com/bnw
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
use FireflyIII\Import\JobConfiguration\FinTSConfigurationSteps;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\FinTS\StageImportDataHandler;
use Illuminate\Support\Facades\Log;

/**
 *
 * Class FinTSRoutine
 */
class FinTSRoutine implements RoutineInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
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
        Log::debug(sprintf('Now in FinTSRoutine::run() with status "%s" and stage "%s".', $this->importJob->status, $this->importJob->stage));
        $valid = ['ready_to_run']; // should be only ready_to_run
        if (in_array($this->importJob->status, $valid, true)) {
            switch ($this->importJob->stage) {
                default:
                    throw new FireflyException(sprintf('FinTSRoutine cannot handle stage "%s".', $this->importJob->stage)); // @codeCoverageIgnore
                case FinTSConfigurationSteps::GO_FOR_IMPORT:
                    $this->repository->setStatus($this->importJob, 'running');
                    /** @var StageImportDataHandler $handler */
                    $handler = app(StageImportDataHandler::class);
                    $handler->setImportJob($this->importJob);
                    $handler->run();
                    $transactions = $handler->getTransactions();

                    $this->repository->setTransactions($this->importJob, $transactions);
                    $this->repository->setStatus($this->importJob, 'provider_finished');
                    $this->repository->setStage($this->importJob, 'final');
            }
        }
    }

    /**
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

}
