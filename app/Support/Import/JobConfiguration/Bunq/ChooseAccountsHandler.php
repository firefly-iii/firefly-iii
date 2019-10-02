<?php
/**
 * ChooseAccountsHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Bunq;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account as AccountModel;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class ChooseAccountsHandler
 */
class ChooseAccountsHandler implements BunqJobConfigurationInterface
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
        $config   = $this->repository->getConfiguration($this->importJob);
        $mapping  = $config['mapping'] ?? [];
        $complete = count($mapping) > 0;
        if (true === $complete) {
            // move job to correct stage to download transactions
            $this->repository->setStage($this->importJob, 'go-for-import');
        }

        return $complete;
    }

    /**
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function configureJob(array $data): MessageBag
    {
        $config     = $this->repository->getConfiguration($this->importJob);
        $accounts   = $config['accounts'] ?? [];
        $mapping    = $data['account_mapping'] ?? [];
        $applyRules = 1 === (int)($data['apply_rules'] ?? 0);
        $final      = [];

        /*
         * $ibanToAsset is used to map bunq IBAN's to Firefly III asset accounts. The array is structured like this:
         * 12BUNQ123456.. => 1,
         * 12BUNQ928811.. => 4,
         *
         * And contains the bunq asset account iban (left) and the FF3 asset ID (right).
         *
         * This is used to properly map transfers.
         */
        $ibanToAsset = [];
        Log::debug('Going to map IBANs for easy mapping later on.');
        if (0 === count($accounts)) {
            throw new FireflyException('No bunq accounts found. Import cannot continue.'); // @codeCoverageIgnore
        }
        if (0 === count($mapping)) {
            $messages = new MessageBag;
            $messages->add('nomap', (string)trans('import.bunq_no_mapping'));

            return $messages;
        }
        foreach ($mapping as $bunqId => $localId) {
            $bunqId  = (int)$bunqId;
            $localId = (int)$localId;

            Log::debug(sprintf('Now trying to link bunq acount #%d with Firefly III account %d', $bunqId, $localId));

            // validate each
            $bunqId    = $this->validBunqAccount($bunqId);
            $accountId = $this->validLocalAccount($localId);

            Log::debug(sprintf('After validation: bunq account #%d with Firefly III account %d', $bunqId, $localId));

            $bunqIban = $this->getBunqIban($bunqId);

            Log::debug(sprintf('IBAN for bunq account #%d is "%s"', $bunqId, $bunqIban));
            if (null !== $bunqIban) {
                $ibanToAsset[$bunqIban] = $accountId; // @codeCoverageIgnore
            }
            $final[$bunqId] = $accountId;
        }
        $config['mapping']     = $final;
        $config['bunq-iban']   = $ibanToAsset;
        $config['apply-rules'] = $applyRules;
        $this->repository->setConfiguration($this->importJob, $config);

        Log::info('Account mapping: ', $final);
        Log::info('Bunq IBAN array: ', $ibanToAsset);

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     * @throws FireflyException
     */
    public function getNextData(): array
    {
        $config   = $this->repository->getConfiguration($this->importJob);
        $accounts = $config['accounts'] ?? [];
        if (0 === count($accounts)) {
            throw new FireflyException('No bunq accounts found. Import cannot continue.'); // @codeCoverageIgnore
        }
        // list the users accounts:
        $collection = $this->accountRepository->getAccountsByType([AccountType::ASSET]);

        $localAccounts = [];
        /** @var AccountModel $localAccount */
        foreach ($collection as $localAccount) {
            $accountId                 = $localAccount->id;
            // TODO we can use getAccountCurrency() instead
            $currencyId                = (int)$this->accountRepository->getMetaValue($localAccount, 'currency_id');
            $currency                  = $this->getCurrency($currencyId);
            $localAccounts[$accountId] = [
                'name' => $localAccount->name,
                'iban' => $localAccount->iban,
                'code' => $currency->code,
            ];
        }

        return [
            'accounts'       => $accounts,
            'local_accounts' => $localAccounts,
        ];
    }

    /**
     * @codeCoverageIgnore
     *
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.bunq.choose-accounts';
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
        $this->accountRepository  = app(AccountRepositoryInterface::class);
        $this->currencyRepository = app(CurrencyRepositoryInterface::class);
        $this->repository->setUser($importJob->user);
        $this->currencyRepository->setUser($importJob->user);
        $this->accountRepository->setUser($importJob->user);
    }

    /**
     * @param int $bunqId
     *
     * @return null|string
     */
    private function getBunqIban(int $bunqId): ?string
    {
        $config   = $this->repository->getConfiguration($this->importJob);
        $accounts = $config['accounts'] ?? [];
        /** @var array $bunqAccount */
        foreach ($accounts as $bunqAccount) {
            if ((int)$bunqAccount['id'] === $bunqId) {
                return $bunqAccount['iban'] ?? null;
            }
        }

        return null;
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
     * @param int $bunqId
     *
     * @return int
     */
    private function validBunqAccount(int $bunqId): int
    {
        $config   = $this->repository->getConfiguration($this->importJob);
        $accounts = $config['accounts'] ?? [];
        /** @var array $bunqAccount */
        foreach ($accounts as $bunqAccount) {
            if ((int)$bunqAccount['id'] === $bunqId) {
                return $bunqId;
            }
        }

        return 0;
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
}
