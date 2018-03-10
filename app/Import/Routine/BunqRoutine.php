<?php
/**
 * BunqRoutine.php
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
use Illuminate\Support\Collection;
use Log;

/**
 * Class BunqRoutine
 */
class BunqRoutine implements RoutineInterface
{
    /** @var Collection */
    public $errors;
    /** @var Collection */
    public $journals;
    /** @var int */
    public $lines = 0;
    /** @var ImportJob */
    private $job;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * ImportRoutine constructor.
     */
    public function __construct()
    {
        $this->journals = new Collection;
        $this->errors   = new Collection;
    }

    /**
     * @return Collection
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        return $this->journals;
    }

    /**
     * @return int
     */
    public function getLines(): int
    {
        return $this->lines;
    }

    /**
     *
     * @return bool
     * @throws FireflyException
     */
    public function run(): bool
    {
        Log::info(sprintf('Start with import job %s using Bunq.', $this->job->key));
        set_time_limit(0);
        // check if job has token first!
        $stage = $this->getConfig()['stage'] ?? 'unknown';

        switch ($stage) {
            case 'initial':
                // get customer and token:
                $this->runStageInitial();
                break;
            case 'default':
                throw new FireflyException(sprintf('No action for stage %s!', $stage));
                break;
            //            case 'has-token':

            //                // import routine does nothing at this point:
            //                break;
            //            case 'user-logged-in':
            //                $this->runStageLoggedIn();
            //                break;
            //            case 'have-account-mapping':
            //                $this->runStageHaveMapping();
            //                break;
            //            default:
            //                throw new FireflyException(sprintf('Cannot handle stage %s', $stage));
            //        }
            //
            //        return true;
        }

        return true;
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);
    }

    /**
     *
     */
    protected function runStageInitial()
    {
        $this->setStatus('running');

        // get session server




        die(' in run stage initial');
    }

    /**
     * Shorthand method.
     */
    private function addStep()
    {
        $this->repository->addStepsDone($this->job, 1);
    }

    /**
     * Shorthand
     *
     * @param int $steps
     */
    private function addTotalSteps(int $steps)
    {
        $this->repository->addTotalSteps($this->job, $steps);
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * Shorthand method.
     *
     * @return array
     */
    private function getExtendedStatus(): array
    {
        return $this->repository->getExtendedStatus($this->job);
    }

    /**
     * Shorthand method.
     *
     * @return string
     */
    private function getStatus(): string
    {
        return $this->repository->getStatus($this->job);
    }

    /**
     * Shorthand.
     *
     * @param array $config
     */
    private function setConfig(array $config): void
    {
        $this->repository->setConfiguration($this->job, $config);

        return;
    }

    /**
     * Shorthand method.
     *
     * @param array $extended
     */
    private function setExtendedStatus(array $extended): void
    {
        $this->repository->setExtendedStatus($this->job, $extended);

        return;
    }

    /**
     * Shorthand.
     *
     * @param string $status
     */
    private function setStatus(string $status): void
    {
        $this->repository->setStatus($this->job, $status);
    }
}