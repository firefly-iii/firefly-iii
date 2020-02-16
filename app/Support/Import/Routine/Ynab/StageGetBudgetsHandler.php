<?php
/**
 * StageGetBudgetsHandler.php
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

namespace FireflyIII\Support\Import\Routine\Ynab;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Ynab\Request\GetBudgetsRequest;
use Log;

/**
 * Class StageGetBudgetsHandler
 */
class StageGetBudgetsHandler
{

    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug('Now in StageGetBudgetsHandler::run()');
        // grab access token from job:
        $configuration = $this->repository->getConfiguration($this->importJob);
        $token         = $configuration['access_token'];
        $request       = new GetBudgetsRequest;
        $request->setAccessToken($token);
        $request->call();

        // store budgets in users preferences.
        $configuration['budgets'] = $request->budgets;
        $this->repository->setConfiguration($this->importJob, $configuration);
        Log::debug(sprintf('Found %d budgets', count($request->budgets)));
        if (0 === count($request->budgets)) {
            throw new FireflyException('It seems this user has zero budgets or an error prevented Firefly III from reading them.');
        }
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}
