<?php
/**
 * StageAuthenticatedHandler.php
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

namespace FireflyIII\Support\Import\Routine\Spectre;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Request\ListAccountsRequest;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use Log;

/**
 * Class StageAuthenticatedHandler
 */
class StageAuthenticatedHandler
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * User has selected a login (a bank). Will grab all accounts from this bank.
     * Then make user pick some to import from.
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug('Now in StageAuthenticatedHandler::run()');
        // grab a list of logins.
        $config = $this->importJob->configuration;
        $logins = $config['all-logins'] ?? [];
        Log::debug(sprintf('%d logins in config', \count($logins)));
        if (\count($logins) === 0) {
            // get logins from Spectre.
            $logins               = $this->getLogins();
            $config['all-logins'] = $logins;
        }

        $selectedLogin = $config['selected-login'] ?? 0;
        $login         = null;
        Log::debug(sprintf('$selectedLogin is %d', $selectedLogin));
        foreach ($logins as $loginArray) {
            $loginId = $loginArray['id'] ?? -1;
            if ($loginId === $selectedLogin) {
                Log::debug('Selected login is in the array with logins.');
                $login = new Login($loginArray);
            }
        }
        if (null === $login) {
            Log::debug('Login is null, simply use the first one from the array.');
            $login = new Login($logins[0]);
        }

        // with existing login we can grab accounts from this login.
        $accounts           = $this->getAccounts($login);
        $config['accounts'] = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $config['accounts'][] = $account->toArray();
        }
        $this->repository->setConfiguration($this->importJob, $config);

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

    /**
     * @param Login $login
     *
     * @return array
     * @throws FireflyException
     */
    private function getAccounts(Login $login): array
    {
        Log::debug(sprintf('Now in StageAuthenticatedHandler::getAccounts() for login #%d', $login->getId()));
        $request = new ListAccountsRequest($this->importJob->user);
        $request->setLogin($login);
        $request->call();
        $accounts = $request->getAccounts();
        Log::debug(sprintf('Found %d accounts using login', \count($accounts)));

        return $accounts;
    }

    /**
     * @return Customer
     * @throws FireflyException
     */
    private function getCustomer(): Customer
    {
        Log::debug('Now in stageNewHandler::getCustomer()');
        $customer = $this->getExistingCustomer();
        if (null === $customer) {
            Log::debug('The customer is NULL, will fire a newCustomerRequest.');
            $newCustomerRequest = new NewCustomerRequest($this->importJob->user);
            $customer           = $newCustomerRequest->getCustomer();

        }
        Log::debug('The customer is not null.');

        return $customer;
    }

    /**
     * @return Customer|null
     * @throws FireflyException
     */
    private function getExistingCustomer(): ?Customer
    {
        Log::debug('Now in ChooseLoginHandler::getExistingCustomer()');
        $preference = app('preferences')->getForUser($this->importJob->user, 'spectre_customer');
        if (null !== $preference) {
            Log::debug('Customer is in user configuration');
            $customer = new Customer($preference->data);

            return $customer;
        }
        Log::debug('Customer is not in user config');
        $customer           = null;
        $getCustomerRequest = new ListCustomersRequest($this->importJob->user);
        $getCustomerRequest->call();
        $customers = $getCustomerRequest->getCustomers();

        Log::debug(sprintf('Found %d customer(s)', \count($customers)));
        /** @var Customer $current */
        foreach ($customers as $current) {
            if ('default_ff3_customer' === $current->getIdentifier()) {
                $customer = $current;
                Log::debug('Found the correct customer.');
                app('preferences')->setForUser($this->importJob->user, 'spectre_customer', $customer->toArray());
                break;
            }
        }

        return $customer;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    private function getLogins(): array
    {
        $customer = $this->getCustomer();
        $request  = new ListLoginsRequest($this->importJob->user);
        $request->setCustomer($customer);
        $request->call();
        $logins = $request->getLogins();
        $return = [];
        /** @var Login $login */
        foreach ($logins as $login) {
            $return[] = $login->toArray();
        }

        return $return;
    }


}