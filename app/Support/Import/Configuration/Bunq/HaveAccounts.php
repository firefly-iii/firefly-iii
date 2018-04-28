<?php
/**
 * HaveAccounts.php
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

namespace FireflyIII\Support\Import\Configuration\Bunq;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Import\Configuration\ConfigurationInterface;
use Illuminate\Support\Collection;

/**
 * Class HaveAccounts
 */
class HaveAccounts implements ConfigurationInterface
{
    /** @var ImportJob */
    private $job;

    /**
     * Get the data necessary to show the configuration screen.
     *
     * @return array
     */
    public function getData(): array
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        $config             = $this->job->configuration;
        $collection         = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $defaultCurrency    = app('amount')->getDefaultCurrency();
        $dbAccounts         = [];
        /** @var Account $dbAccount */
        foreach ($collection as $dbAccount) {
            $id              = $dbAccount->id;
            $currencyId      = (int)$accountRepository->getMetaValue($dbAccount, 'currency_id');
            $currency        = $currencyRepository->findNull($currencyId);
            $dbAccounts[$id] = [
                'account'  => $dbAccount,
                'currency' => $currency ?? $defaultCurrency,
            ];
        }

        // loop Bunq accounts:
        /**
         * @var int   $index
         * @var array $bunqAccount
         */
        foreach ($config['accounts'] as $index => $bunqAccount) {
            // find accounts with currency code
            $code                                  = $bunqAccount['currency'];
            $selection                             = $this->filterAccounts($dbAccounts, $code);
            $config['accounts'][$index]['options'] = app('expandedform')->makeSelectList($selection);
        }


        return [
            'config' => $config,
        ];
    }

    /**
     * Return possible warning to user.
     *
     * @return string
     */
    public function getWarningMessage(): string
    {
        return '';
    }

    /**
     * @param ImportJob $job
     *
     * @return ConfigurationInterface
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * Store the result.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeConfiguration(array $data): bool
    {
        $accounts = $data['bunq_account_id'] ?? [];
        $mapping  = [];
        foreach ($accounts as $bunqId) {
            $bunqId   = (int)$bunqId;
            $doImport = (int)($data['do_import'][$bunqId] ?? 0.0) === 1;
            $account  = (int)($data['import'][$bunqId] ?? 0.0);
            if ($doImport) {
                $mapping[$bunqId] = $account;
            }
        }
        $config                    = $this->job->configuration;
        $config['accounts-mapped'] = $mapping;
        $this->job->configuration  = $config;
        $this->job->save();

        return true;
    }

    /**
     * @param array  $dbAccounts
     * @param string $code
     *
     * @return Collection
     */
    private function filterAccounts(array $dbAccounts, string $code): Collection
    {
        $collection = new Collection;
        foreach ($dbAccounts as $accountId => $data) {
            if ($data['currency']->code === $code) {
                $collection->push($data['account']);
            }
        }

        return $collection;
    }
}
