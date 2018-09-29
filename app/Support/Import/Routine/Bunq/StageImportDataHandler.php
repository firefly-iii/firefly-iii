<?php
/**
 * StageImportDataHandler.php
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

namespace FireflyIII\Support\Import\Routine\Bunq;

use bunq\Model\Generated\Endpoint\Payment as BunqPayment;
use bunq\Model\Generated\Object\LabelMonetaryAccount;
use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account as LocalAccount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Services\Bunq\ApiContext;
use FireflyIII\Services\Bunq\Payment;
use Log;

/**
 * Class StageImportDataHandler
 */
class StageImportDataHandler
{
    private const DOWNLOAD_BACKWARDS = 1;
    private const DOWNLOAD_FORWARDS  = 2;

    /** @var bool */
    public $stillRunning;
    /** @var AccountFactory */
    private $accountFactory;
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var ImportJob */
    private $importJob;
    /** @var array */
    private $jobConfiguration;
    /** @var ImportJobRepositoryInterface */
    private $repository;
    /** @var float */
    private $timeStart;
    /** @var array */
    private $transactions;

    public function __construct()
    {
        $this->stillRunning = true;
        $this->timeStart    = microtime(true);

    }

    /**
     * @codeCoverageIgnore
     * @return array
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        $this->getContext();
        $config                 = $this->repository->getConfiguration($this->importJob);
        $accounts               = $config['accounts'] ?? [];
        $mapping                = $config['mapping'] ?? [];
        $collection             = [[]];
        $this->jobConfiguration = $config;
        /** @var array $bunqAccount */
        foreach ($accounts as $bunqAccount) {
            $bunqAccountId = $bunqAccount['id'] ?? 0;
            $localId       = $mapping[$bunqAccountId] ?? 0;
            Log::debug(sprintf('Looping accounts, now at bunq account #%d and local account #%d', $bunqAccountId, $localId));
            if (0 !== $localId && 0 !== $bunqAccountId) {
                $localAccount = $this->getLocalAccount((int)$localId);
                $collection[] = $this->getTransactionsFromBunq($bunqAccountId, $localAccount);
            }
        }
        $totalSet           = array_merge(...$collection);
        $this->transactions = $totalSet;
    }

    /**
     * @param ImportJob $importJob
     *
     * @return void
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->transactions      = [];
        $this->importJob         = $importJob;
        $this->repository        = app(ImportJobRepositoryInterface::class);
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->accountFactory    = app(AccountFactory::class);
        $this->repository->setUser($importJob->user);
        $this->accountRepository->setUser($importJob->user);
        $this->accountFactory->setUser($importJob->user);
    }

    /**
     * @param BunqPayment  $payment
     * @param LocalAccount $source
     *
     * @return array
     * @throws FireflyException
     */
    private function convertPayment(BunqPayment $payment, int $bunqAccountId, LocalAccount $source): array
    {
        Log::debug(sprintf('Now at payment with ID #%d', $payment->getId()));
        $type         = TransactionType::WITHDRAWAL;
        $counterParty = $payment->getCounterpartyAlias();
        $amount       = $payment->getAmount();
        $paymentId    = $payment->getId();

        Log::debug(sprintf('Amount is %s %s', $amount->getCurrency(), $amount->getValue()));
        $expected = AccountType::EXPENSE;
        if (1 === bccomp($amount->getValue(), '0')) {
            // amount + means that its a deposit.
            $expected = AccountType::REVENUE;
            $type     = TransactionType::DEPOSIT;
            Log::debug('Will make opposing account revenue.');
        }
        $destination = $this->convertToAccount($counterParty, $expected);

        // switch source and destination if necessary.
        if (1 === bccomp($amount->getValue(), '0')) {
            Log::debug('Will make it a deposit.');
            [$source, $destination] = [$destination, $source];
        }

        if ($source->accountType->type === AccountType::ASSET && $destination->accountType->type === AccountType::ASSET) {
            $type = TransactionType::TRANSFER;
            Log::debug('Both are assets, will make transfer.');
        }
        $created   = new Carbon($payment->getCreated());
        $storeData = [
            'user'               => $this->importJob->user_id,
            'type'               => $type,
            'date'               => $created->format('Y-m-d'),
            'description'        => $payment->getDescription(),
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => null,
            'bill_name'          => null,
            'tags'               => [$payment->getType(), $payment->getSubType()],
            'internal_reference' => $paymentId,
            'external_id'        => $paymentId,
            'notes'              => null,
            'bunq_payment_id'    => $paymentId,
            'original-source'    => sprintf('bunq-v%s', config('firefly.version')),
            'transactions'       => [
                // single transaction:
                [
                    'description'           => null,
                    'amount'                => $amount->getValue(),
                    'currency_id'           => null,
                    'currency_code'         => $amount->getCurrency(),
                    'foreign_amount'        => null,
                    'foreign_currency_id'   => null,
                    'foreign_currency_code' => null,
                    'budget_id'             => null,
                    'budget_name'           => null,
                    'category_id'           => null,
                    'category_name'         => null,
                    'source_id'             => $source->id,
                    'source_name'           => null,
                    'destination_id'        => $destination->id,
                    'destination_name'      => null,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];

        return $storeData;
    }

    /**
     * @param LabelMonetaryAccount $party
     * @param string               $expectedType
     *
     * @return LocalAccount
     * @throws FireflyException
     */
    private function convertToAccount(LabelMonetaryAccount $party, string $expectedType): LocalAccount
    {

        Log::debug(sprintf('in convertToAccount() with LabelMonetaryAccount: %s', ''));
        if (null !== $party->getIban()) {
            // find account in 'bunq-iban' array first.
            $bunqIbans = $this->jobConfiguration['bunq-iban'] ?? [];
            if (isset($bunqIbans[$party->getIban()])) {
                $accountId = (int)$bunqIbans[$party->getIban()];
                $result    = $this->accountRepository->findNull($accountId);
                if (null !== $result) {
                    Log::debug(
                        sprintf('Search for #%s (based on IBAN %s) resulted in account %s (#%d)', $accountId, $party->getIban(), $result->name, $result->id)
                    );

                    return $result;
                }
            }

            // find opposing party by IBAN second.
            $result = $this->accountRepository->findByIbanNull($party->getIban(), [$expectedType]);
            if (null !== $result) {
                Log::debug(sprintf('Search for %s resulted in account %s (#%d)', $party->getIban(), $result->name, $result->id));

                return $result;
            }

            // try to find asset account just in case:
            if ($expectedType !== AccountType::ASSET) {
                $result = $this->accountRepository->findByIbanNull($party->getIban(), [AccountType::ASSET]);
                if (null !== $result) {
                    Log::debug(sprintf('Search for Asset "%s" resulted in account %s (#%d)', $party->getIban(), $result->name, $result->id));

                    return $result;
                }
            }
        }

        // create new account:
        $data    = [
            'user_id'         => $this->importJob->user_id,
            'iban'            => $party->getIban(),
            'name'            => $party->getLabelUser()->getDisplayName(),
            'account_type_id' => null,
            'accountType'     => $expectedType,
            'virtualBalance'  => null,
            'active'          => true,
        ];
        $account = $this->accountFactory->create($data);
        Log::debug(
            sprintf(
                'Converted label monetary account %s to %s account %s (#%d)',
                $party->getLabelUser()->getDisplayName(),
                $expectedType,
                $account->name, $account->id
            )
        );

        return $account;
    }

    /**
     * @throws FireflyException
     */
    private function getContext(): void
    {
        /** @var Preference $preference */
        $preference = app('preferences')->getForUser($this->importJob->user, 'bunq_api_context', null);
        if (null !== $preference && '' !== (string)$preference->data) {
            // restore API context
            /** @var ApiContext $apiContext */
            $apiContext = app(ApiContext::class);
            $apiContext->fromJson($preference->data);

            return;
        }
        throw new FireflyException('The bunq API context is unexpectedly empty.'); // @codeCoverageIgnore
    }

    /**
     * Get the direction in which we must download.
     *
     * @param int $bunqAccountId
     *
     * @return int
     */
    private function getDirection(int $bunqAccountId): int
    {
        Log::debug(sprintf('Now in getDirection(%d)', $bunqAccountId));

        // if oldest transaction ID is 0, AND the newest transaction is 0
        // we don't know about this account, so we must go backward in time.
        $oldest = \Preferences::getForUser($this->importJob->user, sprintf('bunq-oldest-transaction-%d', $bunqAccountId), 0);
        $newest = \Preferences::getForUser($this->importJob->user, sprintf('bunq-newest-transaction-%d', $bunqAccountId), 0);

        if (0 === $oldest->data && 0 === $newest->data) {
            Log::debug(sprintf('Oldest tranaction ID is %d and newest tranasction ID is %d, so go backwards.', $oldest->data, $newest->data));

            return self::DOWNLOAD_BACKWARDS;
        }

        // if newest is not zero but oldest is zero, go forward.
        if (0 === $oldest->data && 0 !== $newest->data) {
            Log::debug(sprintf('Oldest tranaction ID is %d and newest tranasction ID is %d, so go forwards.', $oldest->data, $newest->data));

            return self::DOWNLOAD_FORWARDS;
        }

        Log::debug(sprintf('Oldest tranaction ID is %d and newest tranasction ID is %d, so go backwards.', $oldest->data, $newest->data));

        return self::DOWNLOAD_BACKWARDS;
    }

    /**
     * @param int $accountId
     *
     * @return LocalAccount
     * @throws FireflyException
     */
    private function getLocalAccount(int $accountId): LocalAccount
    {
        $account = $this->accountRepository->findNull($accountId);
        if (null === $account) {
            throw new FireflyException(sprintf('Cannot find Firefly III asset account with ID #%d. Job must stop now.', $accountId)); // @codeCoverageIgnore
        }
        if ($account->accountType->type !== AccountType::ASSET) {
            throw new FireflyException(sprintf('Account with ID #%d is not an asset account. Job must stop now.', $accountId)); // @codeCoverageIgnore
        }

        return $account;
    }

    /**
     * @param int          $bunqAccountId
     * @param LocalAccount $localAccount
     *
     * @return array
     * @throws FireflyException
     */
    private function getTransactionsFromBunq(int $bunqAccountId, LocalAccount $localAccount): array
    {
        Log::debug(sprintf('Now in getTransactionsFromBunq(%d).', $bunqAccountId));

        $direction = $this->getDirection($bunqAccountId);
        $return    = [];
        if (self::DOWNLOAD_BACKWARDS === $direction) {
            // go back either from NULL or from ID.
            // ID is the very last transaction downloaded from bunq.
            $preference    = \Preferences::getForUser($this->importJob->user, sprintf('bunq-oldest-transaction-%d', $bunqAccountId), 0);
            $transactionId = 0 === $preference->data ? null : $preference->data;
            $return        = $this->goBackInTime($bunqAccountId, $localAccount, $transactionId);
        }
        if (self::DOWNLOAD_FORWARDS === $direction) {
            // go forward from ID. There is no NULL, young padawan
            $return = $this->goForwardInTime($bunqAccountId, $localAccount);
        }

        return $return;
    }

    /**
     * This method downloads the transactions from bunq going back in time. Assuming bunq
     * is fairly consistent with the transactions it provides through the API, the method
     * will store both the highest and the lowest transaction ID downloaded in this manner.
     *
     * The highest transaction ID is used to continue forward in time. The lowest is used to continue
     * even further back in time.
     *
     * The lowest transaction ID can also be given to this method as a parameter (as $startTransaction).
     *
     * @param int          $bunqAccountId
     * @param LocalAccount $localAccount
     * @param int          $startTransaction
     *
     * @return array
     * @throws FireflyException
     */
    private function goBackInTime(int $bunqAccountId, LocalAccount $localAccount, int $startTransaction = null): array
    {
        Log::debug(sprintf('Now in goBackInTime(#%d, #%s, #%s).', $bunqAccountId, $localAccount->id, $startTransaction));
        $hasMoreTransactions = true;
        $olderId             = $startTransaction;
        $oldestTransaction   = null;
        $newestTransaction   = null;
        $count               = 0;
        $return              = [];

        /*
         * Do a loop during which we run:
         */
        while ($hasMoreTransactions && $this->timeRunning() < 25) {
            Log::debug(sprintf('Now in loop #%d', $count));
            Log::debug(sprintf('Now running for %s seconds.', $this->timeRunning()));

            /*
             * Send request to bunq.
             */
            /** @var Payment $paymentRequest */
            $paymentRequest = app(Payment::class);
            $params         = ['count' => 53, 'older_id' => $olderId];
            $response       = $paymentRequest->listing($bunqAccountId, $params);
            $pagination     = $response->getPagination();
            Log::debug('Params for the request to bunq are: ', $params);

            /*
             * If pagination is not null, we can go back even further.
             */
            if (null !== $pagination) {
                $olderId = $pagination->getOlderId();
                Log::debug(sprintf('Pagination object is not null, new olderID is "%s"', $olderId));
            }

            /*
             * Loop the results from bunq
             */
            Log::debug('Now looping results from bunq...');
            /** @var BunqPayment $payment */
            foreach ($response->getValue() as $index => $payment) {
                $return[]  = $this->convertPayment($payment, $bunqAccountId, $localAccount);
                $paymentId = $payment->getId();
                /*
                 * If oldest and newest transaction are null, they have to be set:
                 */
                $oldestTransaction = $oldestTransaction ?? $paymentId;
                $newestTransaction = $newestTransaction ?? $paymentId;

                /*
                 * Then, overwrite if appropriate
                 */
                $oldestTransaction = $paymentId < $oldestTransaction ? $paymentId : $oldestTransaction;
                $newestTransaction = $paymentId > $newestTransaction ? $paymentId : $newestTransaction;
            }

            /*
             * After the loop, check if Firefly III must loop again.
             */
            Log::debug(sprintf('Count of result is now %d', \count($return)));
            $count++;
            if (null === $olderId) {
                Log::debug('Older ID is NULL, so stop looping cause we are done!');
                $hasMoreTransactions = false;
                $this->stillRunning  = false;
                /*
                 * We no longer care about the oldest transaction ID:
                 */
                $oldestTransaction = 0;
            }
            if (null === $pagination) {
                Log::debug('No pagination object, stop looping.');
                $hasMoreTransactions = false;
                $this->stillRunning  = false;
                /*
                 * We no longer care about the oldest transaction ID:
                 */
                $oldestTransaction = 0;
            }
            sleep(1);


        }
        // store newest and oldest tranasction ID to be used later:
        \Preferences::setForUser($this->importJob->user, sprintf('bunq-oldest-transaction-%d', $bunqAccountId), $oldestTransaction);
        \Preferences::setForUser($this->importJob->user, sprintf('bunq-newest-transaction-%d', $bunqAccountId), $newestTransaction);

        return $return;
    }

    /**
     * @param int          $bunqAccountId
     * @param LocalAccount $localAccount
     *
     * @return array
     * @throws FireflyException
     */
    private function goForwardInTime(int $bunqAccountId, LocalAccount $localAccount): array
    {
        Log::debug(sprintf('Now in goForwardInTime(%d).', $bunqAccountId));
        $hasMoreTransactions = true;
        $count               = 0;
        $return              = [];
        $newestTransaction   = null;

        /*
         * Go forward from the newest transaction we know about:
         */
        $preferenceName  = sprintf('bunq-newest-transaction-%d', $bunqAccountId);
        $transactionPref = \Preferences::getForUser($this->importJob->user, $preferenceName, 0);
        $newerId         = (int)$transactionPref->data;

        /*
         * Run a loop.
         */
        while ($hasMoreTransactions && $this->timeRunning() < 25) {
            /*
             * Debug information:
             */
            Log::debug(sprintf('Now in loop #%d', $count));
            Log::debug(sprintf('Now running for %s seconds.', $this->timeRunning()));

            /*
             * Send a request to bunq.
             */
            /** @var Payment $paymentRequest */
            $paymentRequest = app(Payment::class);
            $params         = ['count' => 53, 'newer_id' => $newerId];
            $response       = $paymentRequest->listing($bunqAccountId, $params);
            $pagination     = $response->getPagination();
            Log::debug('Submit payment request with params', $params);

            /*
             * If pagination is not null, we can go forward even further.
             */
            if (null !== $pagination) {
                $newerId = $pagination->getNewerId();
                Log::debug(sprintf('Pagination object is not null, newerID is "%s"', $newerId));
            }
            Log::debug('Now looping results...');
            /*
             * Process the bunq loop.
             */
            /** @var BunqPayment $payment */
            foreach ($response->getValue() as $payment) {
                $return[]  = $this->convertPayment($payment, $bunqAccountId, $localAccount);
                $paymentId = $payment->getId();

                /*
                 * If oldest and newest transaction are null, they have to be set:
                 */
                $newestTransaction = $newestTransaction ?? $paymentId;

                /*
                 * Then, overwrite if appropriate
                 */
                $newestTransaction = $paymentId > $newestTransaction ? $paymentId : $newestTransaction;
            }

            /*
             * After the loop, check if Firefly III must loop again.
            */
            Log::debug(sprintf('Count of result is now %d', \count($return)));
            $count++;
            if (null === $newerId) {
                Log::debug('Newer ID is NULL, so stop looping cause we are done!');
                $hasMoreTransactions = false;
                $this->stillRunning  = false;
            }
            if (null === $pagination) {
                Log::debug('No pagination object, stop looping.');
                $hasMoreTransactions = false;
                $this->stillRunning  = false;
            }
            sleep(1);
        }

        // store newest tranasction ID to be used later:
        \Preferences::setForUser($this->importJob->user, sprintf('bunq-newest-transaction-%d', $bunqAccountId), $newestTransaction);

        return $return;
    }

    /**
     * @return float
     */
    private function timeRunning(): float
    {
        $time_end = microtime(true);

        return $time_end - $this->timeStart;
    }
}
