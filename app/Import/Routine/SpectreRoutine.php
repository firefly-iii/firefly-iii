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

use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Import\Storage\ImportStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Services\Spectre\Exception\DuplicatedCustomerException;
use FireflyIII\Services\Spectre\Exception\SpectreException;
use FireflyIII\Services\Spectre\Object\Account;
use FireflyIII\Services\Spectre\Object\Customer;
use FireflyIII\Services\Spectre\Object\Login;
use FireflyIII\Services\Spectre\Object\Token;
use FireflyIII\Services\Spectre\Object\Transaction;
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
        if ('configured' === $this->getStatus()) {
            $this->repository->updateStatus($this->job, 'running');
        }
        Log::info(sprintf('Start with import job %s using Spectre.', $this->job->key));
        set_time_limit(0);

        // check if job has token first!
        $stage = $this->getConfig()['stage'] ?? 'unknown';

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

        return true;
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
        $config = $this->getConfig();
        if (!is_null($config['customer'])) {
            $customer = new Customer($config['customer']);

            return $customer;
        }

        $customer           = $this->createCustomer();
        $config['customer'] = [
            'id'         => $customer->getId(),
            'identifier' => $customer->getIdentifier(),
            'secret'     => $customer->getSecret(),
        ];
        $this->setConfig($config);

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
        $config                  = $this->getConfig();
        $config['has-token']     = true;
        $config['token']         = $token->getToken();
        $config['token-expires'] = $token->getExpiresAt()->format('U');
        $config['token-url']     = $token->getConnectUrl();
        $config['stage']         = 'has-token';
        $this->setConfig($config);

        Log::debug('Job config is now', $config);

        // update job, set status to "configuring".
        $this->setStatus('configuring');
        Log::debug(sprintf('Job status is now %s', $this->job->status));
        $this->addStep();
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
            Log::error('Could not find a valid login for this user.');
            $this->repository->addError($this->job, 0, 'Spectre connection failed. Did you use invalid credentials, press Cancel or failed the 2FA challenge?');
            $this->repository->setStatus($this->job, 'error');

            return;
        }
        $this->addStep();

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
        $config             = $this->getConfig();
        $config['accounts'] = $all;
        $config['login']    = $login->toArray();
        $config['stage']    = 'have-accounts';

        $this->setConfig($config);
        $this->setStatus('configuring');
        $this->addStep();

        return;
    }

    /**
     * Shorthand method.
     */
    private function addStep()
    {
        $this->repository->addStepsDone($this->job, 1);
    }

    /**
     * Shorthand
     *
     * @param int $steps
     */
    private function addTotalSteps(int $steps)
    {
        $this->repository->addTotalSteps($this->job, $steps);
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        return $this->repository->getConfiguration($this->job);
    }

    /**
     * Shorthand method.
     *
     * @return array
     */
    private function getExtendedStatus(): array
    {
        return $this->repository->getExtendedStatus($this->job);
    }

    /**
     * Shorthand method.
     *
     * @return string
     */
    private function getStatus(): string
    {
        return $this->repository->getStatus($this->job);
    }

    /**
     * @param array $all
     *
     * @throws FireflyException
     */
    private function importTransactions(array $all)
    {
        Log::debug('Going to import transactions');
        $collection = new Collection;
        // create import objects?
        foreach ($all as $accountId => $data) {
            Log::debug(sprintf('Now at account #%d', $accountId));
            /** @var Transaction $transaction */
            foreach ($data['transactions'] as $transaction) {
                Log::debug(sprintf('Now at transaction #%d', $transaction->getId()));
                /** @var Account $account */
                $account       = $data['account'];
                $importJournal = new ImportJournal;
                $importJournal->setUser($this->job->user);
                $importJournal->asset->setDefaultAccountId($data['import_id']);
                // call set value a bunch of times for various data entries:
                $tags   = [];
                $tags[] = $transaction->getMode();
                $tags[] = $transaction->getStatus();
                if ($transaction->isDuplicated()) {
                    $tags[] = 'possibly-duplicated';
                }
                $extra = $transaction->getExtra()->toArray();
                $notes = '';
                $notes .= strval(trans('import.imported_from_account', ['account' => $account->getName()])) . '  '
                          . "\n"; // double space for newline in Markdown.

                foreach ($extra as $key => $value) {
                    switch ($key) {
                        case 'account_number':
                            $importJournal->setValue(['role' => 'account-number', 'value' => $value]);
                            break;
                        case 'original_category':
                        case 'original_subcategory':
                        case 'customer_category_code':
                        case 'customer_category_name':
                            $tags[] = $value;
                            break;
                        case 'payee':
                            $importJournal->setValue(['role' => 'opposing-name', 'value' => $value]);
                            break;
                        case 'original_amount':
                            $importJournal->setValue(['role' => 'amount_foreign', 'value' => $value]);
                            break;
                        case 'original_currency_code':
                            $importJournal->setValue(['role' => 'foreign-currency-code', 'value' => $value]);
                            break;
                        default:
                            $notes .= $key . ': ' . $value . '  '; // for newline in Markdown.
                    }
                }
                // hash
                $importJournal->setHash($transaction->getHash());

                // account ID (Firefly III account):
                $importJournal->setValue(['role' => 'account-id', 'value' => $data['import_id'], 'mapped' => $data['import_id']]);

                // description:
                $importJournal->setValue(['role' => 'description', 'value' => $transaction->getDescription()]);

                // date:
                $importJournal->setValue(['role' => 'date-transaction', 'value' => $transaction->getMadeOn()->toIso8601String()]);


                // amount
                $importJournal->setValue(['role' => 'amount', 'value' => $transaction->getAmount()]);
                $importJournal->setValue(['role' => 'currency-code', 'value' => $transaction->getCurrencyCode()]);


                // various meta fields:
                $importJournal->setValue(['role' => 'category-name', 'value' => $transaction->getCategory()]);
                $importJournal->setValue(['role' => 'note', 'value' => $notes]);
                $importJournal->setValue(['role' => 'tags-comma', 'value' => join(',', $tags)]);
                $collection->push($importJournal);
            }
        }
        $this->addStep();
        Log::debug(sprintf('Going to try and store all %d them.', $collection->count()));

        $this->addTotalSteps(7 * $collection->count());
        // try to store them (seven steps per transaction)
        $storage = new ImportStorage;

        $storage->setJob($this->job);
        $storage->setDateFormat('Y-m-d\TH:i:sO');
        $storage->setObjects($collection);
        $storage->store();
        Log::info('Back in importTransactions()');

        // link to tag
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($this->job->user);
        $data            = [
            'tag'         => trans('import.import_with_key', ['key' => $this->job->key]),
            'date'        => new Carbon,
            'description' => null,
            'latitude'    => null,
            'longitude'   => null,
            'zoomLevel'   => null,
            'tagMode'     => 'nothing',
        ];
        $tag             = $repository->store($data);
        $extended        = $this->getExtendedStatus();
        $extended['tag'] = $tag->id;
        $this->setExtendedStatus($extended);

        Log::debug(sprintf('Created tag #%d ("%s")', $tag->id, $tag->tag));
        Log::debug('Looping journals...');
        $journalIds = $storage->journals->pluck('id')->toArray();
        $tagId      = $tag->id;
        $this->addTotalSteps(count($journalIds));

        foreach ($journalIds as $journalId) {
            Log::debug(sprintf('Linking journal #%d to tag #%d...', $journalId, $tagId));
            DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journalId, 'tag_id' => $tagId]);
            $this->addStep();
        }
        Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $storage->journals->count(), $tag->id, $tag->tag));

        // set status to "finished"?
        // update job:
        $this->setStatus('finished');
        $this->addStep();

        return;
    }

    /**
     * @throws FireflyException
     * @throws SpectreException
     */
    private function runStageHaveMapping()
    {
        $config   = $this->getConfig();
        $accounts = $config['accounts'] ?? [];
        $all      = [];
        $count    = 0;
        /** @var array $accountArray */
        foreach ($accounts as $accountArray) {
            $account  = new Account($accountArray);
            $importId = intval($config['accounts-mapped'][$account->getid()] ?? 0);
            $doImport = $importId !== 0 ? true : false;
            if (!$doImport) {
                Log::debug(sprintf('Will NOT import from Spectre account #%d ("%s")', $account->getId(), $account->getName()));
                continue;
            }
            // grab all transactions
            $listTransactionsRequest = new ListTransactionsRequest($this->job->user);
            $listTransactionsRequest->setAccount($account);
            $listTransactionsRequest->call();
            $transactions           = $listTransactionsRequest->getTransactions();
            $all[$account->getId()] = [
                'account'      => $account,
                'import_id'    => $importId,
                'transactions' => $transactions,
            ];
            $count                  += count($transactions);
        }
        Log::debug(sprintf('Total number of transactions: %d', $count));
        $this->addStep();


        $this->importTransactions($all);
    }

    /**
     * Shorthand.
     *
     * @param array $config
     */
    private function setConfig(array $config): void
    {
        $this->repository->setConfiguration($this->job, $config);

        return;
    }

    /**
     * Shorthand method.
     *
     * @param array $extended
     */
    private function setExtendedStatus(array $extended): void
    {
        $this->repository->setExtendedStatus($this->job, $extended);

        return;
    }

    /**
     * Shorthand.
     *
     * @param string $status
     */
    private function setStatus(string $status): void
    {
        $this->repository->setStatus($this->job, $status);
    }
}
