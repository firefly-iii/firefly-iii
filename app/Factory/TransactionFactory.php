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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
use FireflyIII\Validation\AccountValidator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var AccountValidator */
    private $accountValidator;
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
        $this->accountValidator  = app(AccountValidator::class);
    }

    /**
     * @param Account             $account
     * @param TransactionCurrency $currency
     * @param string              $amount
     *
     * @return Transaction|null
     */
    public function create(Account $account, TransactionCurrency $currency, string $amount): ?Transaction
    {
        $result = null;
        $data   = [
            'reconciled'              => false,
            'account_id'              => $account->id,
            'transaction_journal_id'  => $this->journal->id,
            'description'             => null,
            'transaction_currency_id' => $currency->id,
            'amount'                  => $amount,
            'foreign_amount'          => null,
            'foreign_currency_id'     => null,
            'identifier'              => 0,
        ];
        try {
            $result = Transaction::create($data);
        } catch (QueryException $e) {
            Log::error(sprintf('Could not create transaction: %s', $e->getMessage()), $data);
        }
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
     * @param NullArrayObject          $data
     * @param TransactionCurrency      $currency
     * @param TransactionCurrency|null $foreignCurrency
     *
     * @return Collection
     * @throws FireflyException
     */
    public function createPair(NullArrayObject $data, TransactionCurrency $currency, ?TransactionCurrency $foreignCurrency): Collection
    {
        // validate source and destination using a new Validator.
        $this->validateAccounts($data);

        // create or get source and destination accounts:
        $sourceAccount      = $this->getAccount('source', (int)$data['source_id'], $data['source_name']);
        $destinationAccount = $this->getAccount('destination', (int)$data['destination_id'], $data['destination_name']);

        $amount        = $this->getAmount($data['amount']);
        $foreignAmount = $this->getForeignAmount($data['foreign_amount']);
        $one           = $this->create($sourceAccount, $currency, app('steam')->negative($amount));
        $two           = $this->create($destinationAccount, $currency, app('steam')->positive($amount));

        $one->reconciled = $data['reconciled'] ?? false;
        $two->reconciled = $data['reconciled'] ?? false;

        // add foreign currency info to $one and $two if necessary.
        if (null !== $foreignCurrency) {
            $one->foreign_currency_id = $foreignCurrency->id;
            $two->foreign_currency_id = $foreignCurrency->id;
            $one->foreign_amount      = app('steam')->negative($foreignAmount);
            $two->foreign_amount      = app('steam')->positive($foreignAmount);
        }


        $one->save();
        $two->save();

        return new Collection([$one, $two]);

    }

    /**
     * @param string      $direction
     * @param int|null    $accountId
     * @param string|null $accountName
     *
     * @return Account
     * @throws FireflyException
     */
    public function getAccount(string $direction, ?int $accountId, ?string $accountName): Account
    {
        // some debug logging:
        Log::debug(sprintf('Now in getAccount(%s, %d, %s)', $direction, $accountId, $accountName));

        // final result:
        $result = null;

        // expected type of source account, in order of preference
        /** @var array $array */
        $array         = config('firefly.expected_source_types');
        $expectedTypes = $array[$direction];
        unset($array);

        // and now try to find it, based on the type of transaction.
        $transactionType = $this->journal->transactionType->type;
        $message = 'Based on the fact that the transaction is a %s, the %s account should be in: %s';
        Log::debug(sprintf($message, $transactionType, $direction, implode(', ', $expectedTypes[$transactionType])));

        // first attempt, find by ID.
        if (null !== $accountId) {
            $search = $this->accountRepository->findNull($accountId);
            if (null !== $search && in_array($search->accountType->type, $expectedTypes[$transactionType], true)) {
                Log::debug(
                    sprintf('Found "account_id" object  for %s: #%d, "%s" of type %s', $direction, $search->id, $search->name, $search->accountType->type)
                );
                $result = $search;
            }
        }

        // second attempt, find by name.
        if (null === $result && null !== $accountName) {
            Log::debug('Found nothing by account ID.');
            // find by preferred type.
            $source = $this->accountRepository->findByName($accountName, [$expectedTypes[$transactionType][0]]);
            // or any expected type.
            $source = $source ?? $this->accountRepository->findByName($accountName, $expectedTypes[$transactionType]);

            if (null !== $source) {
                Log::debug(sprintf('Found "account_name" object for %s: #%d, %s', $direction, $source->id, $source->name));

                $result = $source;
            }
        }

        // return cash account.
        if (null === $result && null === $accountName
            && in_array(AccountType::CASH, $expectedTypes[$transactionType], true)) {
            $result = $this->accountRepository->getCashAccount();
        }

        // return new account.
        if (null === $result) {
            $accountName = $accountName ?? '(no name)';
            // final attempt, create it.
            $preferredType = $expectedTypes[$transactionType][0];
            if (AccountType::ASSET === $preferredType) {
                throw new FireflyException(sprintf('TransactionFactory: Cannot create asset account with ID #%d or name "%s".', $accountId, $accountName));
            }

            $result = $this->accountRepository->store(
                [
                    'account_type_id' => null,
                    'accountType'     => $preferredType,
                    'name'            => $accountName,
                    'active'          => true,
                    'iban'            => null,
                ]
            );
        }

        return $result;
    }

    /**
     * @param string $amount
     *
     * @return string
     * @throws FireflyException
     */
    public function getAmount(string $amount): string
    {
        if ('' === $amount) {
            throw new FireflyException(sprintf('The amount cannot be an empty string: "%s"', $amount));
        }
        if (0 === bccomp('0', $amount)) {
            throw new FireflyException(sprintf('The amount seems to be zero: "%s"', $amount));
        }

        return $amount;
    }

    /**
     * @param string|null $amount
     *
     * @return string
     */
    public function getForeignAmount(?string $amount): ?string
    {
        $result = null;
        if (null === $amount) {
            Log::debug('No foreign amount info in array. Return NULL');

            return null;
        }
        if ('' === $amount) {
            Log::debug('Foreign amount is empty string, return NULL.');

            return null;
        }
        if (0 === bccomp('0', $amount)) {
            Log::debug('Foreign amount is 0.0, return NULL.');

            return null;
        }
        Log::debug(sprintf('Foreign amount is %s', $amount));

        return $amount;
    }


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
     * @param NullArrayObject $data
     *
     * @throws FireflyException
     */
    private function validateAccounts(NullArrayObject $data): void
    {
        $transactionType = $data['type'] ?? 'invalid';
        $this->accountValidator->setTransactionType($transactionType);

        // validate source account.
        $sourceId    = isset($data['source_id']) ? (int)$data['source_id'] : null;
        $sourceName  = $data['source_name'] ?? null;
        $validSource = $this->accountValidator->validateSource($sourceId, $sourceName);

        // do something with result:
        if (false === $validSource) {
            throw new FireflyException($this->accountValidator->sourceError);
        }
        // validate destination account
        $destinationId    = isset($data['destination_id']) ? (int)$data['destination_id'] : null;
        $destinationName  = $data['destination_name'] ?? null;
        $validDestination = $this->accountValidator->validateDestination($destinationId, $destinationName);
        // do something with result:
        if (false === $validDestination) {
            throw new FireflyException($this->accountValidator->destError);
        }
    }
}
