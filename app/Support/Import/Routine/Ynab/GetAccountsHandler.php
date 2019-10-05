<?php
/**
 * GetAccountsHandler.php
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

namespace FireflyIII\Support\Import\Routine\Ynab;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Ynab\Request\GetAccountsRequest;

/**
 * Class GetAccountsHandler
 */
class GetAccountsHandler
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Get list of accounts for the selected budget.
     *
     * @throws FireflyException
     */
    public function run(): void
    {

        $config         = $this->repository->getConfiguration($this->importJob);
        $selectedBudget = $config['selected_budget'] ?? '';
        if ('' === $selectedBudget) {
            $firstBudget = $config['budgets'][0] ?? false;
            if (false === $firstBudget) {
                throw new FireflyException('The configuration contains no budget. Erroring out.');
            }
            $selectedBudget            = $firstBudget['id'];
            $config['selected_budget'] = $selectedBudget;
        }
        $token             = $config['access_token'];
        $request           = new GetAccountsRequest;
        $request->budgetId = $selectedBudget;
        $request->setAccessToken($token);
        $request->call();
        $config['accounts'] = $request->accounts;
        $this->repository->setConfiguration($this->importJob, $config);
        if (0 === count($config['accounts'])) {
            throw new FireflyException('This budget contains zero accounts.');
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
