<?php
/**
 * SelectBudgetHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Ynab;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class SelectBudgetHandler
 */
class SelectBudgetHandler implements YnabJobConfigurationInterface
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var Collection */
    private $accounts;
    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;
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
        Log::debug('Now in SelectBudgetHandler::configComplete');
        $configuration  = $this->repository->getConfiguration($this->importJob);
        $selectedBudget = $configuration['selected_budget'] ?? '';
        if ('' !== $selectedBudget) {
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
        Log::debug('Now in SelectBudgetHandler::configureJob');
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
        Log::debug('Now in SelectBudgetHandler::getNextData');
        $configuration = $this->repository->getConfiguration($this->importJob);
        $budgets       = $configuration['budgets'] ?? [];
        $available     = [];
        $notAvailable  = [];
        $total         = count($budgets);
        foreach ($budgets as $budget) {
            if ($this->haveAssetWithCurrency($budget['currency_code'])) {
                Log::debug('Add budget to available list.');
                $available[$budget['id']] = $budget['name'] . ' (' . $budget['currency_code'] . ')';
                continue;
            }
            Log::debug('Add budget to notAvailable list.');
            $notAvailable[$budget['id']] = $budget['name'] . ' (' . $budget['currency_code'] . ')';

        }

        return [
            'available'     => $available,
            'not_available' => $notAvailable,
            'total'         => $total,
        ];
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        Log::debug('Now in SelectBudgetHandler::getNextView');

        return 'import.ynab.select-budgets';
    }

    /**
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob          = $importJob;
        $this->repository         = app(ImportJobRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->accountRepository  = app(AccountRepositoryInterface::class);

        $this->repository->setUser($importJob->user);
        $this->currencyRepository->setUser($importJob->user);
        $this->accountRepository->setUser($importJob->user);

        $this->accounts = $this->accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    private function haveAssetWithCurrency(string $code): bool
    {
        $currency = $this->currencyRepository->findByCodeNull($code);
        if (null === $currency) {
            Log::debug(sprintf('No currency X found with code "%s"', $code));

            return false;
        }
        /** @var Account $account */
        foreach ($this->accounts as $account) {
            // TODO we can use getAccountCurrency() instead
            $currencyId = (int)$this->accountRepository->getMetaValue($account, 'currency_id');
            Log::debug(sprintf('Currency of %s is %d (looking for %d).', $account->name, $currencyId, $currency->id));
            if ($currencyId === $currency->id) {
                Log::debug('Return true!');

                return true;
            }
        }
        Log::debug('Found nothing, return false.');

        return false;
    }
}
