<?php
/**
 * YnabRoutine.php
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
use FireflyIII\Support\Import\Routine\Ynab\GetAccountsHandler;
use FireflyIII\Support\Import\Routine\Ynab\ImportDataHandler;
use FireflyIII\Support\Import\Routine\Ynab\StageGetAccessHandler;
use FireflyIII\Support\Import\Routine\Ynab\StageGetBudgetsHandler;
use Log;

/**
 * Class YnabRoutine
 */
class YnabRoutine implements RoutineInterface
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
        Log::debug(sprintf('Now in YNAB routine::run() with status "%s" and stage "%s".', $this->importJob->status, $this->importJob->stage));
        $valid = ['ready_to_run']; // should be only ready_to_run
        if (in_array($this->importJob->status, $valid, true)) {

            // get access token from YNAB
            if ('get_access_token' === $this->importJob->stage) {
                // list all of the users accounts.
                $this->repository->setStatus($this->importJob, 'running');
                /** @var StageGetAccessHandler $handler */
                $handler = app(StageGetAccessHandler::class);
                $handler->setImportJob($this->importJob);
                $handler->run();

                // back to correct stage:
                $this->repository->setStatus($this->importJob, 'ready_to_run');
                $this->repository->setStage($this->importJob, 'get_budgets');

                return;
            }
            if ('get_budgets' === $this->importJob->stage) {
                $this->repository->setStatus($this->importJob, 'running');
                /** @var StageGetBudgetsHandler $handler */
                $handler = app(StageGetBudgetsHandler::class);
                $handler->setImportJob($this->importJob);
                $handler->run();

                // count budgets in job, to determine next step.
                $configuration = $this->repository->getConfiguration($this->importJob);
                $budgets       = $configuration['budgets'] ?? [];

                // if more than 1 budget, select budget first.
                if (count($budgets) > 1) {
                    $this->repository->setStage($this->importJob, 'select_budgets');
                    $this->repository->setStatus($this->importJob, 'need_job_config');

                    return;
                }

                if (1 === count($budgets)) {
                    $this->repository->setStatus($this->importJob, 'ready_to_run');
                    $this->repository->setStage($this->importJob, 'get_accounts');
                }

                return;
            }
            if ('get_accounts' === $this->importJob->stage) {
                $this->repository->setStatus($this->importJob, 'running');

                /** @var GetAccountsHandler $handler */
                $handler = app(GetAccountsHandler::class);
                $handler->setImportJob($this->importJob);
                $handler->run();

                $this->repository->setStage($this->importJob, 'select_accounts');
                $this->repository->setStatus($this->importJob, 'need_job_config');

                return;
            }
            if ('go-for-import' === $this->importJob->stage) {
                $this->repository->setStatus($this->importJob, 'running');
                $this->repository->setStage($this->importJob, 'do_import');
                /** @var ImportDataHandler $handler */
                $handler = app(ImportDataHandler::class);
                $handler->setImportJob($this->importJob);
                $handler->run();
                $this->repository->setStatus($this->importJob, 'provider_finished');
                $this->repository->setStage($this->importJob, 'final');

                return;
            }

            throw new FireflyException(sprintf('YNAB import routine cannot handle stage "%s"', $this->importJob->stage));
        }
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
}
