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

use FireflyIII\Models\ImportJob;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
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
     */
    public function run(): bool
    {
        if ('configured' !== $this->job->status) {
            Log::error(sprintf('Job %s is in state "%s" so it cannot be started.', $this->job->key, $this->job->status));

            return false;
        }
        set_time_limit(0);
        Log::info(sprintf('Start with import job %s using Spectre.', $this->job->key));
        // create customer if user does not have one:
        $customer = $this->getCustomer();

        // list all logins present at Spectre
        $logins = $this->listLogins($customer);

        // use latest (depending on status, and if login exists for selected country + provider)
        $country    = $this->job->configuration['country'];
        $providerId = $this->job->configuration['provider'];
        $login      = $this->filterLogins($logins, $country, $providerId);

        // create new login if list is empty or no login exists.
        if (is_null($login)) {
            $login = $this->createLogin($customer);
            die('new login');
        }

        echo '<pre>';
        print_r($logins);
        exit;

        return true;
    }

    /**
     * @param Customer $customer
     */
    protected function createLogin(Customer $customer) {

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

        // store customer. Not sure where. User preference? TODO
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
        var_dump($preference->data);
        exit;
    }

    /**
     * Return login belonging to country and provider
     * TODO must return Login object, not array
     *
     * @param array  $logins
     * @param string $country
     * @param int    $providerId
     *
     * @return array|null
     */
    private function filterLogins(array $logins, string $country, int $providerId): ?array
    {
        if (count($logins) === 0) {
            return null;
        }
        foreach ($logins as $login) {
            die('do some filter');
        }

        return null;
    }

    /**
     * @return array
     */
    private function listLogins(Customer $customer): array
    {
        $listLoginRequest = new ListLoginsRequest($this->job->user);
        $listLoginRequest->setCustomer($customer);
        $listLoginRequest->call();

        $logins = $listLoginRequest->getLogins();

        return $logins;
    }
}
