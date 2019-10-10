<?php
/**
 * SelectAccountsHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Ynab;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class SelectAccountsHandler
 */
class SelectAccountsHandler implements YnabJobConfigurationInterface
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
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
        Log::debug('Now in SelectAccountsHandler::configurationComplete()');
        $config  = $this->importJob->configuration;
        $mapping = $config['mapping'] ?? [];
        if (count($mapping) > 0) {
            // mapping is complete.
            Log::debug('Looks like user has mapped YNAB accounts to Firefly III accounts', $mapping);
            $this->repository->setStage($this->importJob, 'go-for-import');

            return true;
        }

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
        Log::debug('Now in SelectAccountsHandler::configureJob()', $data);
        $config     = $this->importJob->configuration;
        $mapping    = $data['account_mapping'] ?? [];
        $final      = [];
        $applyRules = 1 === (int)($data['apply_rules'] ?? 0);
        foreach ($mapping as $ynabId => $localId) {
            // validate each
            $ynabId    = $this->validYnabAccount($ynabId);
            $accountId = $this->validLocalAccount((int)$localId);
            if (0 !== $accountId) {
                $final[$ynabId] = $accountId;
            }
        }
        Log::debug('Final mapping is:', $final);
        $messages              = new MessageBag;
        $config['mapping']     = $final;
        $config['apply-rules'] = $applyRules;
        $this->repository->setConfiguration($this->importJob, $config);
        if ($final === ['' => 0] || 0 === count($final)) {
            $messages->add('count', (string)trans('import.ynab_no_mapping'));
        }

        return $messages;
    }

    /**
     * Get data for config view.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {

        Log::debug('Now in ChooseAccountsHandler::getnextData()');
        $config       = $this->importJob->configuration;
        $ynabAccounts = $config['accounts'] ?? [];
        $budget       = $this->getSelectedBudget();
        if (0 === count($ynabAccounts)) {
            throw new FireflyException('It seems you have no accounts with this budget. The import cannot continue.'); // @codeCoverageIgnore
        }
        // list the users accounts:
        $ffAccounts = $this->accountRepository->getAccountsByType([AccountType::ASSET]);

        $array = [];
        /** @var Account $account */
        foreach ($ffAccounts as $account) {
            $accountId         = $account->id;
            // TODO we can use getAccountCurrency() instead
            $currencyId        = (int)$this->accountRepository->getMetaValue($account, 'currency_id');
            $currency          = $this->getCurrency($currencyId);
            $array[$accountId] = [
                'name' => $account->name,
                'iban' => $account->iban,
                'code' => $currency->code,
            ];
        }

        return [
            'budget'        => $budget,
            'ynab_accounts' => $ynabAccounts,
            'ff_accounts'   => $array,
        ];
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.ynab.accounts';
    }

    /**
     * @codeCoverageIgnore
     * Set the import job.
     *
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob          = $importJob;
        $this->repository         = app(ImportJobRepositoryInterface::class);
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
        $this->currencyRepository->setUser($importJob->user);
        $this->accountRepository->setUser($importJob->user);
    }

    /**
     * @param int $currencyId
     *
     * @return TransactionCurrency
     */
    private function getCurrency(int $currencyId): TransactionCurrency
    {
        $currency = $this->currencyRepository->findNull($currencyId);
        if (null === $currency) {
            return app('amount')->getDefaultCurrencyByUser($this->importJob->user);
        }

        return $currency;

    }

    /**
     * @return array
     */
    private function getSelectedBudget(): array
    {
        $config   = $this->repository->getConfiguration($this->importJob);
        $budgets  = $config['budgets'] ?? [];
        $selected = $config['selected_budget'] ?? '';
        foreach ($budgets as $budget) {
            if ($budget['id'] === $selected) {
                return $budget;
            }
        }

        return $budgets[0] ?? [];
    }

    /**
     * @param int $accountId
     *
     * @return int
     */
    private function validLocalAccount(int $accountId): int
    {
        $account = $this->accountRepository->findNull($accountId);
        if (null === $account) {
            return 0;
        }

        return $accountId;
    }

    /**
     * @param string $accountId
     *
     * @return string
     */
    private function validYnabAccount(string $accountId): string
    {
        $config   = $this->importJob->configuration;
        $accounts = $config['accounts'] ?? [];
        foreach ($accounts as $account) {
            if ($account['id'] === $accountId) {
                return $accountId;
            }
        }

        return '';
    }
}
