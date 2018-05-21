<?php
/**
 * ChooseLoginHandler.php
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

namespace FireflyIII\Support\Import\JobConfiguration\Spectre;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\NewCustomerRequest;
use Illuminate\Support\MessageBag;
use Log;


/**
 * Class ChooseLoginHandler
 *
 */
class ChooseLoginHandler implements SpectreConfigurationInterface
{
    /** @var ImportJob */
    private $importJob;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Return true when this stage is complete.
     *
     * @return bool
     */
    public function configurationComplete(): bool
    {
        Log::debug('Now in ChooseLoginHandler::configurationComplete()');
        $config = $this->importJob->configuration;
        if (isset($config['selected-login'])) {
            Log::debug('config[selected-login] is set, return true.');

            return true;
        }
        Log::debug('config[selected-login] is not set, return false.');

        return false;
    }

    /**
     * Store the job configuration.
     *
     * @param array $data
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function configureJob(array $data): MessageBag
    {
        Log::debug('Now in ChooseLoginHandler::configureJob()');
        $selectedLogin            = (int)$data['spectre_login_id'];
        $config                   = $this->importJob->configuration;
        $config['selected-login'] = $selectedLogin;
        $this->repository->setConfiguration($this->importJob, $config);
        Log::debug(sprintf('The selected login by the user is #%d', $selectedLogin));

        // if selected login is zero, create a new one.
        if ($selectedLogin === 0) {
            Log::debug('Login is zero, get a new customer + token and store it in config.');
            $customer = $this->getCustomer();
            // get a token for the user and redirect to next stage
            $token              = $this->getToken($customer);
            $config['customer'] = $customer->toArray();
            $config['token']    = $token->toArray();
            $this->repository->setConfiguration($this->importJob, $config);
            // move job to correct stage to redirect to Spectre:
            $this->repository->setStage($this->importJob, 'do-authenticate');

            return new MessageBag;

        }
        $this->repository->setStage($this->importJob, 'authenticated');

        return new MessageBag;
    }

    /**
     * Get data for config view.
     *
     * @return array
     */
    public function getNextData(): array
    {
        Log::debug('Now in ChooseLoginHandler::getNextData()');
        $config = $this->importJob->configuration;
        $data   = ['logins' => []];
        $logins = $config['all-logins'] ?? [];
        Log::debug(sprintf('Count of logins in configuration is %d.', \count($logins)));
        foreach ($logins as $login) {
            $data['logins'][] = new Login($login);
        }

        return $data;
    }

    /**
     * Get the view for this stage.
     *
     * @return string
     */
    public function getNextView(): string
    {
        return 'import.spectre.choose-login';
    }

    /**
     * Set the import job.
     *
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
     * @param Customer $customer
     *
     * @throws FireflyException
     * @return Token
     */
    private function getToken(Customer $customer): Token
    {
        Log::debug('Now in ChooseLoginHandler::ChooseLoginsHandler::getToken()');
        $request = new CreateTokenRequest($this->importJob->user);
        $request->setUri(route('import.job.status.index', [$this->importJob->key]));
        $request->setCustomer($customer);
        $request->call();
        Log::debug('Call to get token is finished');

        return $request->getToken();
    }
}