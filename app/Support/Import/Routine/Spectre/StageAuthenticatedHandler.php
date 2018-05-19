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
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Request\ListAccountsRequest;

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
    public function run()
    {
        // grab a list of logins.
        $config = $this->importJob->configuration;
        $logins = $config['all-logins'] ?? [];
        if (\count($logins) === 0) {
            throw new FireflyException('StageAuthenticatedHandler expects more than 0 logins. Apologies, the import has stopped.');
        }

        $selectedLogin = $config['selected-login'];
        $login         = null;
        foreach ($logins as $loginArray) {
            $loginId = $loginArray['id'] ?? -1;
            if ($loginId === $selectedLogin) {
                $login = new Login($loginArray);
            }
        }
        if (null === $login) {
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
        $request = new ListAccountsRequest($this->importJob->user);
        $request->setLogin($login);
        $request->call();
        $accounts = $request->getAccounts();

        return $accounts;
    }


}