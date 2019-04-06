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
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
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
    use JournalServiceTrait;

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
        Log::debug('Going to create a pair of transactions.');
        Log::debug(sprintf('Source info: ID #%d, name "%s"', $data['source_id'], $data['source_name']));
        Log::debug(sprintf('Destination info: ID #%d, name "%s"', $data['destination_id'], $data['destination_name']));
        // validate source and destination using a new Validator.
        $this->validateAccounts($data);

        // create or get source and destination accounts:
        $type               = $this->journal->transactionType->type;
        $sourceAccount      = $this->getAccount($type, 'source', (int)$data['source_id'], $data['source_name']);
        $destinationAccount = $this->getAccount($type, 'destination', (int)$data['destination_id'], $data['destination_name']);

        $amount        = $this->getAmount($data['amount']);
        $foreignAmount = $this->getForeignAmount($data['foreign_amount']);
        $one           = $this->create($sourceAccount, $currency, app('steam')->negative($amount));
        $two           = $this->create($destinationAccount, $currency, app('steam')->positive($amount));

        $one->reconciled = $data['reconciled'] ?? false;
        $two->reconciled = $data['reconciled'] ?? false;

        // add foreign currency info to $one and $two if necessary.
        if (null !== $foreignCurrency && null !== $foreignAmount) {
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
        $this->accountValidator->setUser($this->journal->user);
        $this->accountValidator->setTransactionType($transactionType);

        // validate source account.
        $sourceId    = isset($data['source_id']) ? (int)$data['source_id'] : null;
        $sourceName  = $data['source_name'] ?? null;
        $validSource = $this->accountValidator->validateSource($sourceId, $sourceName);

        // do something with result:
        if (false === $validSource) {
            throw new FireflyException($this->accountValidator->sourceError);
        }
        Log::debug('Source seems valid.');
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
