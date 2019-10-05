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
use Log;

/**
 * Class ChooseAccountsHandler
 *
 */
class ChooseAccountsHandler implements SpectreJobConfigurationInterface
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
        Log::debug('Now in ChooseAccountsHandler::configurationComplete()');
        $config         = $this->importJob->configuration;
        $importAccounts = $config['account_mapping'] ?? [];
        $complete       = count($importAccounts) > 0 && $importAccounts !== [0 => 0];
        if ($complete) {
            Log::debug('Looks like user has mapped import accounts to Firefly III accounts', $importAccounts);
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
        Log::debug('Now in ChooseAccountsHandler::configureJob()', $data);
        $config     = $this->importJob->configuration;
        $mapping    = $data['account_mapping'] ?? [];
        $final      = [];
        $applyRules = 1 === (int)($data['apply_rules'] ?? 0);
        foreach ($mapping as $spectreId => $localId) {
            // validate each
            $spectreId         = $this->validSpectreAccount((int)$spectreId);
            $accountId         = $this->validLocalAccount((int)$localId);
            $final[$spectreId] = $accountId;

        }
        Log::debug('Final mapping is:', $final);
        $messages                  = new MessageBag;
        $config['account_mapping'] = $final;
        $config['apply-rules']     = $applyRules;
        $this->repository->setConfiguration($this->importJob, $config);
        if ($final === [0 => 0] || 0 === count($final)) {
            $messages->add('count', (string)trans('import.spectre_no_mapping'));
        }

        return $messages;
    }

    /**
     * Get data for config view.
     *
     * @return array
     * @throws FireflyException
     *
     */
    public function getNextData(): array
    {
        Log::debug('Now in ChooseAccountsHandler::getnextData()');
        $config   = $this->importJob->configuration;
        $accounts = $config['accounts'] ?? [];
        if (0 === count($accounts)) {
            throw new FireflyException('It seems you have no accounts with this bank. The import cannot continue.'); // @codeCoverageIgnore
        }
        $converted = [];
        foreach ($accounts as $accountArray) {
            $converted[] = new SpectreAccount($accountArray);
        }

        // get the provider that was used.
        $login    = null;
        $logins   = $config['all-logins'] ?? [];
        $selected = $config['selected-login'] ?? 0;
        if (0 === count($logins)) {
            throw new FireflyException('It seems you have no configured logins in this import job. The import cannot continue.'); // @codeCoverageIgnore
        }
        Log::debug(sprintf('Selected login to use is %d', $selected));
        if (0 === $selected) {
            $login = new Login($logins[0]);
            Log::debug(sprintf('Will use login %d (%s %s)', $login->getId(), $login->getProviderName(), $login->getCountryCode()));
        }
        if (0 !== $selected) {
            foreach ($logins as $loginArray) {
                $loginId = $loginArray['id'] ?? -1;
                if ($loginId === $selected) {
                    $login = new Login($loginArray);
                    Log::debug(sprintf('Will use login %d (%s %s)', $login->getId(), $login->getProviderName(), $login->getCountryCode()));
                }
            }
        }
        if (null === $login) {
            throw new FireflyException('Was not able to determine which login to use. The import cannot continue.'); // @codeCoverageIgnore
        }

        // list the users accounts:
        $accounts = $this->accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE]);

        $array = [];
        /** @var AccountModel $account */
        foreach ($accounts as $account) {
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
            'accounts'    => $converted,
            'ff_accounts' => $array,
            'login'       => $login,

        ];
    }

    /**
     * @codeCoverageIgnore
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.spectre.accounts';
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
