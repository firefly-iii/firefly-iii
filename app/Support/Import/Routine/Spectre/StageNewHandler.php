<?php
/**
 * StageNewHandler.php
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
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Support\Import\Information\GetSpectreCustomerTrait;
use Log;

/**
 * Class StageNewHandler
 *
 */
class StageNewHandler
{
    use GetSpectreCustomerTrait;
    /** @var int */
    private $countLogins = 0;
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * @codeCoverageIgnore
     * @return int
     */
    public function getCountLogins(): int
    {
        return $this->countLogins;
    }


    /**
     * Tasks for this stage:
     *
     * - List all of the users logins.
     * - If zero, return to "get-token" stage and make user make a login. That stage redirects here.
     * - If one or more, list and let user select.
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug('Now in ManageLoginsHandler::run()');
        $customer = $this->getCustomer($this->importJob);
        $config   = $this->repository->getConfiguration($this->importJob);

        Log::debug('Going to get a list of logins.');
        /** @var ListLoginsRequest $request */
        $request = app(ListLoginsRequest::class);
        $request->setUser($this->importJob->user);
        $request->setCustomer($customer);
        $request->call();

        $list = $request->getLogins();

        // count is zero?
        $this->countLogins = count($list);
        Log::debug(sprintf('Number of logins is %d', $this->countLogins));
        if ($this->countLogins > 0) {
            $store = [];
            /** @var Login $login */
            foreach ($list as $login) {
                $store[] = $login->toArray();
            }

            $config['all-logins'] = $store;
            $this->repository->setConfiguration($this->importJob, $config);
            Log::debug('Stored all logins in configuration.');
        }
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
}
