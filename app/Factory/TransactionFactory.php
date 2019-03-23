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
use FireflyIII\Support\NullArrayObject;
use FireflyIII\User;
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
        $sourceAccount      = $this->getAccount('source', $data['source'], (int)$data['source_id'], $data['source_name']);
        $destinationAccount = $this->getAccount('destination', $data['destination'], (int)$data['destination_id'], $data['destination_name']);
        $amount             = $this->getAmount($data['amount']);
        $foreignAmount      = $this->getForeignAmount($data['foreign_amount']);

        $this->makeDramaOverAccountTypes($sourceAccount, $destinationAccount);


        $one = $this->create($sourceAccount, $currency, app('steam')->negative($amount));
        $two = $this->create($destinationAccount, $currency, app('steam')->positive($amount));

        $one->reconciled = $data['reconciled'] ?? false;
        $two->reconciled = $data['reconciled'] ?? false;

        // add foreign currency info to $one and $two if necessary.
        if (null !== $foreignCurrency) {
            $one->foreign_currency_id = $foreignCurrency->id;
            $two->foreign_currency_id = $foreignCurrency->id;
            $one->foreign_amount      = $foreignAmount;
            $two->foreign_amount      = $foreignAmount;
        }


        $one->save();
        $two->save();

        return new Collection([$one, $two]);

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
    public function getAccount(string $direction, ?Account $source, ?int $sourceId, ?string $sourceName): Account
    {
        Log::debug(sprintf('Now in getAccount(%s)', $direction));
        Log::debug(sprintf('Parameters: ((account), %s, %s)', var_export($sourceId, true), var_export($sourceName, true)));
        // expected type of source account, in order of preference
        /** @var array $array */
        $array         = config('firefly.expected_source_types');
        $expectedTypes = $array[$direction];
        unset($array);

        // and now try to find it, based on the type of transaction.
        $transactionType = $this->journal->transactionType->type;
        Log::debug(
            sprintf(
                'Based on the fact that the transaction is a %s, the %s account should be in: %s', $transactionType, $direction,
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
                Log::debug(
                    sprintf('Found "account_id" object  for %s: #%d, "%s" of type %s', $direction, $source->id, $source->name, $source->accountType->type)
                );

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
        if (null === $sourceName && \in_array(AccountType::CASH, $expectedTypes[$transactionType], true)) {
            return $this->accountRepository->getCashAccount();
        }
        $sourceName = $sourceName ?? '(no name)';
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
     * This method will throw a Firefly III Exception of the source and destination account types are not OK.
     *
     * @throws FireflyException
     *
     * @param Account $source
     * @param Account $destination
     */
    public function makeDramaOverAccountTypes(Account $source, Account $destination): void
    {
        // if the source is X, then Y is allowed as destination.
        $combinations = config('firefly.source_dests');
        $sourceType   = $source->accountType->type;
        $destType     = $destination->accountType->type;
        $journalType  = $this->journal->transactionType->type;
        $allowed      = $combinations[$journalType][$sourceType] ?? [];
        if (!\in_array($destType, $allowed, true)) {
            throw new FireflyException(
                sprintf(
                    'Journal of type "%s" has a source account of type "%s" and cannot accept a "%s"-account as destination, but only accounts of: %s', $journalType, $sourceType,
                    $destType, implode(', ', $combinations[$journalType][$sourceType])
                )
            );
        }
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
}
