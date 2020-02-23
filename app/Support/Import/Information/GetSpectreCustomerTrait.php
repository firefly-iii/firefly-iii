<?php
/**
 * GetSpectreCustomerTrait.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Support\Import\Information;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use Log;

/**
 * Trait GetSpectreCustomerTrait
 * @codeCoverageIgnore
 */
trait GetSpectreCustomerTrait
{
    /**
     * @param ImportJob $importJob
     *
     * @return Customer
     * @throws FireflyException
     */
    protected function getCustomer(ImportJob $importJob): Customer
    {
        Log::debug('Now in GetSpectreCustomerTrait::getCustomer()');
        $customer = $this->getExistingCustomer($importJob);
        if (null === $customer) {
            Log::debug('The customer is NULL, will fire a newCustomerRequest.');
            /** @var NewCustomerRequest $request */
            $request = app(NewCustomerRequest::class);
            $request->setUser($importJob->user);
            $request->call();

            $customer = $request->getCustomer();

        }

        Log::debug('The customer is not null.');

        return $customer;
    }

    /**
     * @param ImportJob $importJob
     *
     * @return Customer|null
     * @throws FireflyException
     */
    protected function getExistingCustomer(ImportJob $importJob): ?Customer
    {
        Log::debug('Now in GetSpectreCustomerTrait::getExistingCustomer()');
        $customer = null;

        // check users preferences.
        $preference = app('preferences')->getForUser($importJob->user, 'spectre_customer', null);
        if (null !== $preference) {
            Log::debug('Customer is in user configuration');
            $customer = new Customer($preference->data);

            return $customer;
        }

        /** @var ListCustomersRequest $request */
        $request = app(ListCustomersRequest::class);
        $request->setUser($importJob->user);
        $request->call();
        $customers = $request->getCustomers();

        Log::debug(sprintf('Found %d customer(s)', count($customers)));
        /** @var Customer $current */
        foreach ($customers as $current) {
            if ('default_ff3_customer' === $current->getIdentifier()) {
                $customer = $current;
                Log::debug('Found the correct customer.');
                break;
            }
            Log::debug(sprintf('Skip customer with name "%s"', $current->getIdentifier()));
        }
        if (null !== $customer) {
            // store in preferences.
            app('preferences')->setForUser($importJob->user, 'spectre_customer', $customer->toArray());
        }

        return $customer;
    }
}
