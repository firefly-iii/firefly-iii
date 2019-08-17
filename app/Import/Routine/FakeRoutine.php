<?php
/**
 * FakeRoutine.php
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

namespace FireflyIII\Import\Routine;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\Fake\StageAhoyHandler;
use FireflyIII\Support\Import\Routine\Fake\StageFinalHandler;
use FireflyIII\Support\Import\Routine\Fake\StageNewHandler;
use Log;

/**
 * Class FakeRoutine
 */
class FakeRoutine implements RoutineInterface
{
    /** @var ImportJob The import job */
    private $importJob;
    /** @var ImportJobRepositoryInterface Import job repository */
    private $repository;

    /**
     * Fake import routine has three stages:
     *
     * "new": will quietly log gibberish for 15 seconds, then switch to stage "ahoy".
     *        will also set status to "ready_to_run" so it will arrive here again.
     * "ahoy": will log some nonsense and then drop job into status:"need_job_config" to force it back to the job config routine.
     * "final": will do some logging, sleep for 10 seconds and then finish. Generates 5 random transactions.
     *
     * @return void
     * @throws FireflyException
     *
     *
     *
     */
    public function run(): void
    {
        Log::debug(sprintf('Now in run() for fake routine with status: %s', $this->importJob->status));
        if ('ready_to_run' !== $this->importJob->status) {
            throw new FireflyException(sprintf('Fake job should have status "ready_to_run", not "%s"', $this->importJob->status)); // @codeCoverageIgnore
        }

        switch ($this->importJob->stage) {
            default:
                throw new FireflyException(sprintf('Fake routine cannot handle stage "%s".', $this->importJob->stage)); // @codeCoverageIgnore
            case 'new':
                $this->repository->setStatus($this->importJob, 'running');
                /** @var StageNewHandler $handler */
                $handler = app(StageNewHandler::class);
                $handler->run();
                $this->repository->setStage($this->importJob, 'ahoy');
                // set job finished this step:
                $this->repository->setStatus($this->importJob, 'ready_to_run');

                return;
            case 'ahoy':
                $this->repository->setStatus($this->importJob, 'running');
                /** @var StageAhoyHandler $handler */
                $handler = app(StageAhoyHandler::class);
                $handler->run();
                $this->repository->setStatus($this->importJob, 'need_job_config');
                $this->repository->setStage($this->importJob, 'final');
                break;
            case 'final':
                $this->repository->setStatus($this->importJob, 'running');
                /** @var StageFinalHandler $handler */
                $handler = app(StageFinalHandler::class);
                $handler->setImportJob($this->importJob);
                $transactions = $handler->getTransactions();
                $this->repository->setStatus($this->importJob, 'provider_finished');
                $this->repository->setStage($this->importJob, 'final');
                $this->repository->setTransactions($this->importJob, $transactions);
        }
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     *
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}
