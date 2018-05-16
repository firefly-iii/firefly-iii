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
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use Log;

/**
 * Class StageNewHandler
 *
 * @package FireflyIII\Support\Import\Routine\Spectre
 */
class StageNewHandler
{
    /** @var ImportJob */
    private $importJob;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Tasks for this stage:
     *
     * - Get the user's customer from Spectre.
     * - Create a new customer if it does not exist.
     * - Store it in the job either way.
     * - Use it to grab a token.
     * - Store the token in the job.
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        Log::debug('Now in stageNewHandler::run()');
        $customer = $this->getCustomer();
        // get token using customer.
        $token = $this->getToken($customer);

        app('preferences')->setForUser($this->importJob->user, 'spectre_customer', $customer->toArray());
        app('preferences')->setForUser($this->importJob->user, 'spectre_token', $token->toArray());
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
        Log::debug('Now in getExistingCustomer()');
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
                break;
            }
        }

        return $customer;
    }

    /**
     * @param Customer $customer
     *
     * @throws FireflyException
     * @return Token
     */
    private function getToken(Customer $customer): Token
    {
        Log::debug('Now in getToken()');
        $request = new CreateTokenRequest($this->importJob->user);
        $request->setUri(route('import.job.status.index', [$this->importJob->key]));
        $request->setCustomer($customer);
        $request->call();
        Log::debug('Call to get token is finished');

        return $request->getToken();
    }


}