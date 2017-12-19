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
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use Preferences;
use Illuminate\Support\Collection;
use Log;

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


        return true;
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
     */
    protected function createCustomer(): Customer
    {
        $newCustomerRequest = new NewCustomerRequest($this->job->user);
        $newCustomerRequest->call();
        echo '<pre>';
        print_r($newCustomerRequest->getCustomer());
        exit;

    }

    /**
     * @return Customer
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


}
