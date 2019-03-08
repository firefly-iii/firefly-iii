<?php

/**
 * TransactionFactory.php
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

namespace FireflyIII\Factory;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var TransactionJournal */
    private $journal;
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
        $this->accountRepository = app(AccountRepositoryInterface::class);
    }

    //use TransactionServiceTrait;

    /**
     * @param Account             $account
     * @param TransactionCurrency $currency
     * @param string              $amount
     *
     * @return Transaction|null
     */
    public function create(Account $account, TransactionCurrency $currency, string $amount): ?Transaction
    {
        $result = Transaction::create(
            [
                'reconciled'              => false,
                'account_id'              => $account->id,
                'transaction_journal_id'  => $this->journal->id,
                'description'             => null,
                'transaction_currency_id' => $currency->id,
                'amount'                  => $amount,
                'foreign_amount'          => null,
                'foreign_currency_id'     => null,
                'identifier'              => 0,
            ]
        );
        if (null !== $result) {
            Log::debug(
                sprintf(
                    'Created transaction #%d (%s %s), part of journal #%d', $result->id,
                    $currency->code, $amount, $this->journal->id
                )
            );
        }

        return $result;
    }

    /**
     * @param TransactionCurrency $currency
     * @param array               $data
     *
     * @return Collection
     * @throws FireflyException
     */
    public function createPair(TransactionCurrency $currency, array $data): Collection
    {
        $sourceAccount      = $this->getAccount('source', $data['source'], $data['source_id'], $data['source_name']);
        $destinationAccount = $this->getAccount('destination', $data['destination'], $data['destination_id'], $data['destination_name']);
        $amount             = $this->getAmount($data['amount']);

        $one = $this->create($sourceAccount, $currency, app('steam')->negative($amount));
        $two = $this->create($destinationAccount, $currency, app('steam')->positive($amount));

        return new Collection([$one, $two]);

        //        Log::debug('Start of TransactionFactory::createPair()'  );
        //
        //        // type of source account and destination account depends on journal type:
        //        $sourceType      = $this->accountType($journal, 'source');
        //        $destinationType = $this->accountType($journal, 'destination');
        //
        //        Log::debug(sprintf('Journal is a %s.', $journal->transactionType->type));
        //        Log::debug(sprintf('Expect source account to be of type "%s"', $sourceType));
        //        Log::debug(sprintf('Expect source destination to be of type "%s"', $destinationType));
        //
        //        // find source and destination account:
        //        $sourceAccount      = $this->findAccount($sourceType, $data['source'], (int)$data['source_id'], $data['source_name']);
        //        $destinationAccount = $this->findAccount($destinationType, $data['destination'], (int)$data['destination_id'], $data['destination_name']);
        //
        //        if (null === $sourceAccount || null === $destinationAccount) {
        //            $debugData                = $data;
        //            $debugData['source_type'] = $sourceType;
        //            $debugData['dest_type']   = $destinationType;
        //            Log::error('Info about source/dest:', $debugData);
        //            throw new FireflyException('Could not determine source or destination account.');
        //        }
        //
        //        Log::debug(sprintf('Source type is "%s", destination type is "%s"', $sourceAccount->accountType->type, $destinationAccount->accountType->type));
        //
        //        // based on the source type, destination type and transaction type, the system can start throwing FireflyExceptions.
        //        $this->validateTransaction($sourceAccount->accountType->type, $destinationAccount->accountType->type, $journal->transactionType->type);
        //        $source = $this->create(
        //            [
        //                'description'         => null,
        //                'amount'              => app('steam')->negative((string)$data['amount']),
        //                'foreign_amount'      => $data['foreign_amount'] ? app('steam')->negative((string)$data['foreign_amount']): null,
        //                'currency'            => $data['currency'],
        //                'foreign_currency'    => $data['foreign_currency'],
        //                'account'             => $sourceAccount,
        //                'transaction_journal' => $journal,
        //                'reconciled'          => $data['reconciled'],
        //            ]
        //        );
        //        $dest   = $this->create(
        //            [
        //                'description'         => null,
        //                'amount'              => app('steam')->positive((string)$data['amount']),
        //                'foreign_amount'      => $data['foreign_amount'] ? app('steam')->positive((string)$data['foreign_amount']): null,
        //                'currency'            => $data['currency'],
        //                'foreign_currency'    => $data['foreign_currency'],
        //                'account'             => $destinationAccount,
        //                'transaction_journal' => $journal,
        //                'reconciled'          => $data['reconciled'],
        //            ]
        //        );
        //        if (null === $source || null === $dest) {
        //            throw new FireflyException('Could not create transactions.'); // @codeCoverageIgnore
        //        }
        //
        //        return new Collection([$source, $dest]);
    }

    //    /**
    //     * @param array $data
    //     *
    //     * @return Transaction
    //     */
    //    public function create(array $data): ?Transaction
    //    {
    //        $data['foreign_amount'] = '' === (string)$data['foreign_amount'] ? null : $data['foreign_amount'];
    //        Log::debug(sprintf('Create transaction for account #%d ("%s") with amount %s', $data['account']->id, $data['account']->name, $data['amount']));
    //
    //        return Transaction::create(
    //            [
    //                'reconciled'              => $data['reconciled'],
    //                'account_id'              => $data['account']->id,
    //                'transaction_journal_id'  => $data['transaction_journal']->id,
    //                'description'             => $data['description'],
    //                'transaction_currency_id' => $data['currency']->id,
    //                'amount'                  => $data['amount'],
    //                'foreign_amount'          => $data['foreign_amount'],
    //                'foreign_currency_id'     => $data['foreign_currency'] ? $data['foreign_currency']->id : null,
    //                'identifier'              => 0,
    //            ]
    //        );
    //    }

    /**
     * @param TransactionJournal $journal
     */
    public function setJournal(TransactionJournal $journal): void
    {
        $this->journal = $journal;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->accountRepository->setUser($user);
    }

    /**
     * @param string       $direction
     * @param Account|null $source
     * @param int|null     $sourceId
     * @param string|null  $sourceName
     *
     * @return Account
     * @throws FireflyException
     */
    private function getAccount(string $direction, ?Account $source, ?int $sourceId, ?string $sourceName): Account
    {
        // expected type of source account, in order of preference
        $array         = [
            'source'      => [
                TransactionType::WITHDRAWAL      => [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE],
                TransactionType::DEPOSIT         => [AccountType::REVENUE, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE],
                TransactionType::TRANSFER        => [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE],
                TransactionType::OPENING_BALANCE => [AccountType::INITIAL_BALANCE, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT,
                                                     AccountType::MORTGAGE],
                TransactionType::RECONCILIATION  => [AccountType::RECONCILIATION, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT,
                                                     AccountType::MORTGAGE],
            ],
            'destination' => [
                TransactionType::WITHDRAWAL      => [AccountType::EXPENSE, AccountType::ASSET],
                TransactionType::DEPOSIT         => [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE],
                TransactionType::TRANSFER        => [AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE],
                TransactionType::OPENING_BALANCE => [AccountType::INITIAL_BALANCE, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT,
                                                     AccountType::MORTGAGE],
                TransactionType::RECONCILIATION  => [AccountType::RECONCILIATION, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT,
                                                     AccountType::MORTGAGE],
            ],
        ];
        $expectedTypes = $array[$direction];
        unset($array);

        // and now try to find it, based on the type of transaction.
        $transactionType = $this->journal->transactionType->type;
        Log::debug(
            sprintf(
                'Based on the fact that the transaction is a %s, the %s account should be in %s', $transactionType, $direction,
                implode(', ', $expectedTypes[$transactionType])
            )
        );


        // first attempt, check the "source" object.
        if (null !== $source && $source->user_id === $this->user->id && \in_array($source->accountType->type, $expectedTypes[$transactionType], true)) {
            Log::debug(sprintf('Found "account" object for %s: #%d, %s', $direction, $source->id, $source->name));

            return $source;
        }

        // second attempt, find by ID.
        if (null !== $sourceId) {
            $source = $this->accountRepository->findNull($sourceId);
            if (null !== $source && \in_array($source->accountType->type, $expectedTypes[$transactionType], true)) {
                Log::debug(sprintf('Found "account_id" object for %s: #%d, %s', $direction, $source->id, $source->name));

                return $source;
            }
        }

        // third attempt, find by name.
        if (null !== $sourceName) {
            // find by preferred type.
            $source = $this->accountRepository->findByName($sourceName, [$expectedTypes[$transactionType][0]]);
            // or any type.
            $source = $source ?? $this->accountRepository->findByName($sourceName, $expectedTypes[$transactionType]);

            if (null !== $source) {
                Log::debug(sprintf('Found "account_name" object for %s: #%d, %s', $direction, $source->id, $source->name));

                return $source;
            }
        }

        // final attempt, create it.
        $preferredType = $expectedTypes[$transactionType][0];
        if (AccountType::ASSET === $preferredType) {
            throw new FireflyException(sprintf('TransactionFactory: Cannot create asset account with ID #%d or name "%s".', $sourceId, $sourceName));
        }

        return $this->accountRepository->store(
            [
                'account_type_id' => null,
                'accountType'     => $preferredType,
                'name'            => $sourceName,
                'active'          => true,
                'iban'            => null,
            ]
        );
    }

    /**
     * @param string $amount
     *
     * @return string
     * @throws FireflyException
     */
    private function getAmount(string $amount): string
    {
        if ('' === $amount) {
            throw new FireflyException(sprintf('The amount cannot be an empty string: "%s"', $amount));
        }
        if (0 === bccomp('0', $amount)) {
            throw new FireflyException(sprintf('The amount seems to be zero: "%s"', $amount));
        }

        return $amount;
    }
    //
    //    /**
    //     * @param string $sourceType
    //     * @param string $destinationType
    //     * @param string $transactionType
    //     *
    //     * @throws FireflyException
    //     */
    //    private function validateTransaction(string $sourceType, string $destinationType, string $transactionType): void
    //    {
    //        // throw big fat error when source type === dest type and it's not a transfer or reconciliation.
    //        if ($sourceType === $destinationType && $transactionType !== TransactionType::TRANSFER) {
    //            throw new FireflyException(sprintf('Source and destination account cannot be both of the type "%s"', $destinationType));
    //        }
    //        // source must be in this list AND dest must be in this list:
    //        $list = [AccountType::DEFAULT, AccountType::ASSET, AccountType::CREDITCARD, AccountType::CASH, AccountType::DEBT, AccountType::MORTGAGE,
    //                 AccountType::LOAN, AccountType::MORTGAGE];
    //        if (
    //            !\in_array($sourceType, $list, true)
    //            && !\in_array($destinationType, $list, true)) {
    //            throw new FireflyException(sprintf('At least one of the accounts must be an asset account (%s, %s).', $sourceType, $destinationType));
    //        }
    //    }


}
