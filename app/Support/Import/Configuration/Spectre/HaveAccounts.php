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

namespace FireflyIII\Support\Import\Configuration\Spectre;


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
            $currencyId      = intval($dbAccount->getMeta('currency_id'));
            $currency        = $currencyRepository->find($currencyId);
            $dbAccounts[$id] = [
                'account'  => $dbAccount,
                'currency' => is_null($currency->id) ? $defaultCurrency : $currency,
            ];
        }

        // loop Spectre accounts:
        /**
         * @var int   $index
         * @var array $spectreAccount
         */
        foreach ($config['accounts'] as $index => $spectreAccount) {
            // find accounts with currency code
            $code                                  = $spectreAccount['currency_code'];
            $selection                             = $this->filterAccounts($dbAccounts, $code);
            $config['accounts'][$index]['options'] = app('expandedform')->makeSelectList($selection);
        }


        $data = [
            'config' => $config,
        ];


        return $data;
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
        $accounts = $data['spectre_account_id'] ?? [];
        $mapping  = [];
        foreach ($accounts as $spectreId) {
            $spectreId = intval($spectreId);
            $doImport  = intval($data['do_import'][$spectreId] ?? 0) === 1;
            $account   = intval($data['import'][$spectreId] ?? 0);
            if ($doImport) {
                $mapping[$spectreId] = $account;
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