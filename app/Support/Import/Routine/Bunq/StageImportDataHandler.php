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
    /** @var array */
    private $transactions;

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
    private function convertPayment(BunqPayment $payment, LocalAccount $source): array
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
        $return = [];
        // make request:
        /** @var Payment $paymentRequest */
        $paymentRequest = app(Payment::class);
        $result         = $paymentRequest->listing($bunqAccountId, ['count' => 100]);
        // loop result:
        /** @var BunqPayment $payment */
        foreach ($result->getValue() as $payment) {
            $return[] = $this->convertPayment($payment, $localAccount);
        }

        return $return;
    }
}
