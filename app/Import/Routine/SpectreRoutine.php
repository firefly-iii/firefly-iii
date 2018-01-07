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
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Spectre\Exception\DuplicatedCustomerException;
use FireflyIII\Services\Spectre\Exception\SpectreException;
use FireflyIII\Services\Spectre\Object\Account;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Request\CreateTokenRequest;
use FireflyIII\Services\Spectre\Request\ListAccountsRequest;
use FireflyIII\Services\Spectre\Request\ListCustomersRequest;
use FireflyIII\Services\Spectre\Request\ListLoginsRequest;
use FireflyIII\Services\Spectre\Request\ListTransactionsRequest;
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

    /** @var ImportJobRepositoryInterface */
    private $repository;

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
     * A Spectre job that ends up here is either "configured" or "running", and will be set to "running"
     * when it is "configured".
     *
     * Job has several stages, stored in extended status key 'stage'
     *
     * initial: just begun, nothing happened. action: get a customer and a token. Next status: has-token
     * has-token: redirect user to sandstorm, make user login. set job to: user-logged-in
     * user-logged-in: customer has an attempt. action: analyse/get attempt and go for next status.
     *                 if attempt failed: job status is error, save a warning somewhere?
     *                 if success, try to get accounts. Save in config key 'accounts'. set status: have-accounts and "configuring"
     *
     * have-accounts: make user link accounts and select accounts to import from.
     *
     * If job is "configuring" and stage "have-accounts" then present the accounts and make user link them to
     * own asset accounts. Store this mapping, set config to "have-account-mapping" and job status configured".
     *
     * have-account-mapping: start downloading transactions?
     *
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \FireflyIII\Services\Spectre\Exception\SpectreException
     */
    public function run(): bool
    {
        if ('configured' === $this->job->status) {
            $this->repository->updateStatus($this->job, 'running');
        }
        Log::info(sprintf('Start with import job %s using Spectre.', $this->job->key));
        set_time_limit(0);

        // check if job has token first!
        $config = $this->job->configuration;
        $stage  = $config['stage'];

        switch ($stage) {
            case 'initial':
                // get customer and token:
                $this->runStageInitial();
                break;
            case 'has-token':
                // import routine does nothing at this point:
                break;
            case 'user-logged-in':
                $this->runStageLoggedIn();
                break;
            case 'have-account-mapping':
                $this->runStageHaveMapping();
                break;
            default:
                throw new FireflyException(sprintf('Cannot handle stage %s', $stage));
        }

        var_dump($config);
        exit;

        throw new FireflyException('Application cannot handle this.');
    }

    /**
     * @param ImportJob $job
     */
    public function setJob(ImportJob $job)
    {
        $this->job        = $job;
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($job->user);
    }

    /**
     * @return Customer
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \FireflyIII\Services\Spectre\Exception\SpectreException
     */
    protected function createCustomer(): Customer
    {
        $newCustomerRequest = new NewCustomerRequest($this->job->user);
        $customer           = null;
        try {
            $newCustomerRequest->call();
            $customer = $newCustomerRequest->getCustomer();
        } catch (DuplicatedCustomerException $e) {
            // already exists, must fetch customer instead.
            Log::warning('Customer exists already for user, fetch it.');
        }
        if (is_null($customer)) {
            $getCustomerRequest = new ListCustomersRequest($this->job->user);
            $getCustomerRequest->call();
            $customers = $getCustomerRequest->getCustomers();
            /** @var Customer $current */
            foreach ($customers as $current) {
                if ($current->getIdentifier() === 'default_ff3_customer') {
                    $customer = $current;
                    break;
                }
            }
        }

        Preferences::setForUser($this->job->user, 'spectre_customer', $customer->toArray());

        return $customer;

    }

    /**
     * @return Customer
     * @throws FireflyException
     * @throws \FireflyIII\Services\Spectre\Exception\SpectreException
     */
    protected function getCustomer(): Customer
    {
        $config = $this->job->configuration;
        if (!is_null($config['customer'])) {
            $customer = new Customer($config['customer']);

            return $customer;
        }

        $customer                 = $this->createCustomer();
        $config['customer']       = [
            'id'         => $customer->getId(),
            'identifier' => $customer->getIdentifier(),
            'secret'     => $customer->getSecret(),
        ];
        $this->job->configuration = $config;
        $this->job->save();

        return $customer;
    }

    /**
     * @param Customer $customer
     * @param string   $returnUri
     *
     * @return Token
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \FireflyIII\Services\Spectre\Exception\SpectreException
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

    /**
     * @throws FireflyException
     * @throws SpectreException
     */
    protected function runStageInitial(): void
    {
        Log::debug('In runStageInitial()');

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
        $config['stage']          = 'has-token';
        $this->job->configuration = $config;

        Log::debug('Job config is now', $config);

        // update job, set status to "configuring".
        $this->job->status = 'configuring';
        $this->job->save();
        Log::debug(sprintf('Job status is now %s', $this->job->status));
    }

    /**
     * @throws FireflyException
     * @throws SpectreException
     */
    protected function runStageLoggedIn(): void
    {
        Log::debug('In runStageLoggedIn');
        // list all logins:
        $customer = $this->getCustomer();
        $request  = new ListLoginsRequest($this->job->user);
        $request->setCustomer($customer);
        $request->call();
        $logins = $request->getLogins();
        /** @var Login $final */
        $final = null;
        // loop logins, find the latest with no error in it:
        $time = 0;
        /** @var Login $login */
        foreach ($logins as $login) {
            $attempt     = $login->getLastAttempt();
            $attemptTime = intval($attempt->getCreatedAt()->format('U'));
            if ($attemptTime > $time && is_null($attempt->getFailErrorClass())) {
                $time  = $attemptTime;
                $final = $login;
            }
        }
        if (is_null($final)) {
            throw new FireflyException('No valid login attempt found.');
        }

        // list the users accounts using this login.
        $accountRequest = new ListAccountsRequest($this->job->user);
        $accountRequest->setLogin($login);
        $accountRequest->call();
        $accounts = $accountRequest->getAccounts();

        // store accounts in job:
        $all = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $all[] = $account->toArray();
        }

        // update job:
        $config                   = $this->job->configuration;
        $config['accounts']       = $all;
        $config['login']          = $login->toArray();
        $config['stage']          = 'have-accounts';
        $this->job->configuration = $config;
        $this->job->status        = 'configuring';
        $this->job->save();

        return;
    }

    /**
     *
     */
    private function runStageHaveMapping()
    {
        // for each spectre account id in 'account-mappings'.
        // find FF account
        // get transactions.
        // import?!
        $config   = $this->job->configuration;
        $accounts = $config['accounts'] ?? [];
        /** @var array $accountArray */
        foreach ($accounts as $accountArray) {
            $account  = new Account($accountArray);
            $importId = intval($config['accounts-mapped'][$account->getid()] ?? 0);
            $doImport = $importId !== 0 ? true : false;
            if (!$doImport) {
                continue;
            }
            // import into account
            $listTransactionsRequest = new ListTransactionsRequest($this->job->user);
            $listTransactionsRequest->setAccount($account);
            $listTransactionsRequest->call();
            $transactions = $listTransactionsRequest->getTransactions();
            var_dump($transactions);exit;

        }
        var_dump($config);
        exit;
    }
}
