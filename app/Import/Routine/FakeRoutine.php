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
    /** @var ImportJob */
    private $job;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * FakeRoutine constructor.
     */
    public function __construct()
    {
        $this->repository = app(ImportJobRepositoryInterface::class);
    }


    /**
     * Fake import routine has three stages:
     *
     * "new": will quietly log gibberish for 15 seconds, then switch to stage "ahoy".
     *        will also set status to "ready_to_run" so it will arrive here again.
     * "ahoy": will log some nonsense and then drop job into status:"need_job_config" to force it back to the job config routine.
     * "final": will do some logging, sleep for 10 seconds and then finish. Generates 5 random transactions.
     *
     * @return bool
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug(sprintf('Now in run() for fake routine with status: %s', $this->job->status));
        if ($this->job->status !== 'running') {
            throw new FireflyException('This fake job should not be started.');
        }

        switch ($this->job->stage) {
            default:
                throw new FireflyException(sprintf('Fake routine cannot handle stage "%s".', $this->job->stage));
            case 'new':
                $handler = new StageNewHandler;
                $handler->run();
                $this->repository->setStage($this->job, 'ahoy');
                // set job finished this step:
                $this->repository->setStatus($this->job, 'ready_to_run');

                return;
            case 'ahoy':
                $handler = new StageAhoyHandler;
                $handler->run();
                $this->repository->setStatus($this->job, 'need_job_config');
                $this->repository->setStage($this->job, 'final');
                break;
            case 'final':
                $handler = new StageFinalHandler;
                $handler->setJob($this->job);
                $transactions = $handler->getTransactions();
                $this->repository->setStatus($this->job, 'provider_finished');
                $this->repository->setStage($this->job, 'final');
                $this->repository->setTransactions($this->job, $transactions);
        }
    }

    /**
     * @param ImportJob $job
     *
     * @return mixed
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
        $this->repository->setUser($job->user);
    }
}