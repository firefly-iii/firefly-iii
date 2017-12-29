<?php
/**
 * SpectreRoutine.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Import\Routine;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use Illuminate\Support\Collection;
use Log;
use Preferences;

/**
 * Class FileRoutine
 */
class SpectreRoutine implements RoutineInterface
{
    /** @var Collection */
    public $errors;
    /** @var Collection */
    public $journals;
    /** @var int */
    public $lines = 0;
    /** @var ImportJob */
    private $job;

    /**
     * ImportRoutine constructor.
     */
    public function __construct()
    {
        $this->journals = new Collection;
        $this->errors   = new Collection;
    }

    /**
     * @return Collection
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * @return Collection
     */
    public function getJournals(): Collection
    {
        return $this->journals;
    }

    /**
     * @return int
     */
    public function getLines(): int
    {
        return $this->lines;
    }

    /**
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function run(): bool
    {
        if ('configured' !== $this->job->status) {
            Log::error(sprintf('Job %s is in state "%s" so it cannot be started.', $this->job->key, $this->job->status));

            return false;
        }
        Log::info(sprintf('Start with import job %s using Spectre.', $this->job->key));
        set_time_limit(0);

        // check if job has token first!
        $config   = $this->job->configuration;
        $hasToken = $config['has-token'] ?? false;
        if ($hasToken === false) {
            Log::debug('Job has no token');
            // create customer if user does not have one:
            $customer = $this->getCustomer();
            Log::debug(sprintf('Customer ID is %s', $customer->getId()));
            // use customer to request a token:
            $uri   = route('import.status', [$this->job->key]);
            $token = $this->getToken($customer, $uri);
            Log::debug(sprintf('Token is %s', $token->getToken()));

            // update job, give it the token:
            $config                   = $this->job->configuration;
            $config['has-token']      = true;
            $config['token']          = $token->getToken();
            $config['token-expires']  = $token->getExpiresAt()->format('U');
            $config['token-url']      = $token->getConnectUrl();
            $this->job->configuration = $config;

            Log::debug('Job config is now', $config);

            // update job, set status to "configuring".
            $this->job->status = 'configuring';
            $this->job->save();
            Log::debug(sprintf('Job status is now %s', $this->job->status));

            return true;
        }
        $isRedirected = $config['is-redirected'] ?? false;
        if ($isRedirected === true) {
            // assume user has "used" the token.
            // ...
            // now what?
            throw new FireflyException('Application cannot handle this.');
        }

        throw new FireflyException('Application cannot handle this.');
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job = $job;
    }

    /**
     * @return Customer
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    protected function createCustomer(): Customer
    {
        $newCustomerRequest = new NewCustomerRequest($this->job->user);
        $newCustomerRequest->call();
        $customer = $newCustomerRequest->getCustomer();

        Preferences::setForUser($this->job->user, 'spectre_customer', $customer->toArray());

        return $customer;

    }

    /**
     * @return Customer
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    protected function getCustomer(): Customer
    {
        $preference = Preferences::getForUser($this->job->user, 'spectre_customer', null);
        if (is_null($preference)) {
            return $this->createCustomer();
        }
        $customer = new Customer($preference->data);

        return $customer;
    }

    /**
     * @param Customer $customer
     * @param string   $returnUri
     *
     * @return Token
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    protected function getToken(Customer $customer, string $returnUri): Token
    {
        $request = new CreateTokenRequest($this->job->user);
        $request->setUri($returnUri);
        $request->setCustomer($customer);
        $request->call();
        Log::debug('Call to get token is finished');

        return $request->getToken();

    }
}
