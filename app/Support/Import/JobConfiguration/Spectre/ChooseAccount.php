<?php
/**
 * ChooseAccount.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Spectre;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account as AccountModel;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account as SpectreAccount;
use FireflyIII\Services\Spectre\Object\Login;
use Illuminate\Support\MessageBag;

/**
 * Class ChooseAccount
 *
 * @package FireflyIII\Support\Import\JobConfiguration\Spectre
 */
class ChooseAccount implements SpectreJobConfig
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
        $config         = $this->importJob->configuration;
        $importAccounts = $config['account_mapping'] ?? [];
        $complete       = \count($importAccounts) > 0 && $importAccounts !== [0 => 0];
        if ($complete) {
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
     */
    public function configureJob(array $data): MessageBag
    {
        $config  = $this->importJob->configuration;
        $mapping = $data['account_mapping'] ?? [];
        $final   = [];
        foreach ($mapping as $spectreId => $fireflyIIIId) {
            // validate each
            $spectreId         = $this->validSpectreAccount((int)$spectreId);
            $accountId         = $this->validFireflyIIIAccount((int)$fireflyIIIId);
            $final[$spectreId] = $accountId;

        }
        $messages                  = new MessageBag;
        $config['account_mapping'] = $final;

        $this->repository->setConfiguration($this->importJob, $config);
        if (\count($final) === 0 || $final === [0 => 0]) {
            $messages->add('count', trans('import.spectre_no_mapping'));
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
        $config   = $this->importJob->configuration;
        $accounts = $config['accounts'] ?? [];
        if (\count($accounts) === 0) {
            throw new FireflyException('It seems you have no accounts with this bank. The import cannot continue.');
        }
        $converted = [];
        foreach ($accounts as $accountArray) {
            $converted[] = new SpectreAccount($accountArray);
        }

        // get the provider that was used.
        $login    = null;
        $logins   = $config['all-logins'] ?? [];
        $selected = $config['selected-login'] ?? 0;
        if (\count($logins) === 0) {
            throw new FireflyException('It seems you have no configured logins in this import job. The import cannot continue.');
        }
        if ($selected === 0) {
            $login = new Login($logins[0]);
        }
        if ($selected !== 0) {
            foreach ($logins as $loginArray) {
                $loginId = $loginArray['id'] ?? -1;
                if ($loginId === $selected) {
                    $login = new Login($loginArray);
                }
            }
        }
        if (null === $login) {
            throw new FireflyException('Was not able to determine which login to use. The import cannot continue.');
        }

        // list the users accounts:
        $accounts = $this->accountRepository->getAccountsByType([AccountType::ASSET]);

        $array = [];
        /** @var AccountModel $account */
        foreach ($accounts as $account) {
            $accountId         = $account->id;
            $currencyId        = (int)$this->accountRepository->getMetaValue($account, 'currency_id');
            $currency          = $this->getCurrency($currencyId);
            $array[$accountId] = [
                'name' => $account->name,
                'iban' => $account->iban,
                'code' => $currency->code,
            ];
        }

        return [
            'accounts'    => $converted,
            'ff_accounts' => $array,
            'login'       => $login,

        ];
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.spectre.accounts';
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
     * @param int $accountId
     *
     * @return int
     */
    private function validFireflyIIIAccount(int $accountId): int
    {
        $account = $this->accountRepository->findNull($accountId);
        if (null === $account) {
            return 0;
        }

        return $accountId;
    }

    /**
     * @param int $accountId
     *
     * @return int
     */
    private function validSpectreAccount(int $accountId): int
    {
        $config   = $this->importJob->configuration;
        $accounts = $config['accounts'] ?? [];
        foreach ($accounts as $account) {
            if ((int)$account['id'] === $accountId) {
                return $accountId;
            }
        }

        return 0;
    }
}