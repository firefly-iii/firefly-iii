<?php
/**
 * StageAuthenticatedHandler.php
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

namespace FireflyIII\Support\Import\Routine\Spectre;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Account;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Request\ListAccountsRequest;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Support\Import\Information\GetSpectreCustomerTrait;
use Log;

/**
 * Class StageAuthenticatedHandler
 */
class StageAuthenticatedHandler
{
    use GetSpectreCustomerTrait;
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
        Log::debug(sprintf('%d logins in config', count($logins)));
        if (0 === count($logins)) {
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
                $login = new Login($loginArray);
                Log::debug(sprintf('Selected login "%s" ("%s") which is in the array with logins.', $login->getProviderName(), $login->getCountryCode()));
            }
        }
        if (null === $login) {
            $login = new Login($logins[0]);
            Log::debug(sprintf('Login is null, simply use the first one "%s" ("%s") from the array.', $login->getProviderName(), $login->getCountryCode()));
        }

        // with existing login we can grab accounts from this login.
        $accounts           = $this->getAccounts($login);
        $config['accounts'] = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            Log::debug(sprintf('Found account #%d ("%s") within Login.', $account->getId(), $account->getName()));
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
        /** @var ListAccountsRequest $request */
        $request = app(ListAccountsRequest::class);
        $request->setUser($this->importJob->user);
        $request->setLogin($login);
        $request->call();
        $accounts = $request->getAccounts();
        Log::debug(sprintf('Found %d accounts using login', count($accounts)));

        return $accounts;
    }

    /**
     * @return array
     * @throws FireflyException
     */
    private function getLogins(): array
    {
        Log::debug('Now in StageAuthenticatedHandler::getLogins().');
        $customer = $this->getCustomer($this->importJob);

        /** @var ListLoginsRequest $request */
        $request = app(ListLoginsRequest::class);
        $request->setUser($this->importJob->user);
        $request->setCustomer($customer);
        $request->call();
        $logins = $request->getLogins();
        $return = [];

        Log::debug(sprintf('Found %d logins in users Spectre account.', count($logins)));

        /** @var Login $login */
        foreach ($logins as $login) {
            $return[] = $login->toArray();
        }

        return $return;
    }


}
