<?php
/**
 * SelectBudgetsHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Ynab;

use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class SelectBudgetsHandler
 */
class SelectBudgetsHandler implements YnabJobConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Return true when this stage is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        Log::debug('Now in SelectBudgetsHandler::configComplete');
        $configuration  = $this->repository->getConfiguration($this->importJob);
        $selectedBudget = $configuration['selected_budget'] ?? '';
        if ($selectedBudget !== '') {
            Log::debug(sprintf('Selected budget is %s, config is complete. Return true.', $selectedBudget));
            $this->repository->setStage($this->importJob, 'get_accounts');
            return true;
        }
        Log::debug('User has not selected a budget yet, config is not yet complete.');

        return false;
    }

    /**
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('Now in SelectBudgetsHandler::configureJob');
        $configuration                    = $this->repository->getConfiguration($this->importJob);
        $configuration['selected_budget'] = $data['budget_id'];

        Log::debug(sprintf('Set selected budget to %s', $data['budget_id']));
        Log::debug('Mark job as ready for next stage.');


        $this->repository->setConfiguration($this->importJob, $configuration);

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     */
    public function getNextData(): array
    {
        Log::debug('Now in SelectBudgetsHandler::getNextData');
        $configuration = $this->repository->getConfiguration($this->importJob);
        $budgets       = $configuration['budgets'] ?? [];
        $return        = [];
        foreach ($budgets as $budget) {
            $return[$budget['id']] = $budget['name'] . ' (' . $budget['currency_code'] . ')';
        }

        return [
            'budgets' => $return,
        ];
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('Now in SelectBudgetsHandler::getNextView');

        return 'import.ynab.select-budgets';
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob  = $importJob;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
    }
}