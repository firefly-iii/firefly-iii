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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Models\Account as LocalAccount;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
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
    /** @var int */
    private const DOWNLOAD_BACKWARDS = 1;
    /** @var int */
    private const DOWNLOAD_FORWARDS = 2;

    /** @var bool */
    public $stillRunning;
    /** @var AccountFactory */
    private $accountFactory;
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var PaymentConverter */
    private $converter;
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
        $this->converter    = app(PaymentConverter::class);

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
     * @return bool
     */
    public function isStillRunning(): bool
    {
        return $this->stillRunning;
    }

    /**
     *
     * @throws FireflyException
     */
    public function run(): void
    {
        $this->getContext();
        $this->converter->setImportJob($this->importJob);
        $config                 = $this->repository->getConfiguration($this->importJob);
        $accounts               = $config['accounts'] ?? [];
        $mapping                = $config['mapping'] ?? [];
        $collection             = [[]];
        $this->jobConfiguration = $config;
        /** @var array $bunqAccount */
        foreach ($accounts as $bunqAccount) {
            $bunqAccountId = $bunqAccount['id'] ?? 0;
            $localId       = $mapping[$bunqAccountId] ?? 0;
            if (0 !== $localId && 0 !== $bunqAccountId) {
                Log::info(sprintf('Now at bunq account #%d and local account #%d', $bunqAccountId, $localId));
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
    private function convertPayment(BunqPayment $payment, LocalAccount $source): array
    {
        Log::debug(sprintf('Now at payment with ID #%d', $payment->getId()));

        return $this->converter->convert($payment, $source);
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
            Log::info('For this account we go backwards in time.');
            // go back either from NULL or from ID.
            // ID is the very last transaction downloaded from bunq.
            $preference    = \Preferences::getForUser($this->importJob->user, sprintf('bunq-oldest-transaction-%d', $bunqAccountId), 0);
            $transactionId = 0 === $preference->data ? null : $preference->data;
            $return        = $this->goBackInTime($bunqAccountId, $localAccount, $transactionId);
        }
        if (self::DOWNLOAD_FORWARDS === $direction) {
            Log::info('For this account we go forwards in time.');
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
            $params         = ['count' => 197, 'older_id' => $olderId];
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
                $return[]  = $this->convertPayment($payment, $localAccount);
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
            Log::debug(sprintf('Count of result is now %d', count($return)));
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
            // sleep 2 seconds to prevent hammering bunq.
            sleep(2);
        }
        // store newest and oldest tranasction ID to be used later:
        \Preferences::setForUser($this->importJob->user, sprintf('bunq-oldest-transaction-%d', $bunqAccountId), $oldestTransaction);
        \Preferences::setForUser($this->importJob->user, sprintf('bunq-newest-transaction-%d', $bunqAccountId), $newestTransaction);
        Log::info(sprintf('Downloaded and parsed %d transactions from bunq.', count($return)));

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
            $params         = ['count' => 197, 'newer_id' => $newerId];
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
                $return[]  = $this->convertPayment($payment, $localAccount);
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
            Log::debug(sprintf('Count of result is now %d', count($return)));
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
            // sleep 2 seconds to prevent hammering bunq.
            sleep(2);
        }

        // store newest tranasction ID to be used later:
        \Preferences::setForUser($this->importJob->user, sprintf('bunq-newest-transaction-%d', $bunqAccountId), $newestTransaction);
        Log::info(sprintf('Downloaded and parsed %d transactions from bunq.', count($return)));

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
