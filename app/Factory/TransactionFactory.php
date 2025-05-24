<?php

/**
 * TransactionFactory.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Rules\UniqueIban;
use FireflyIII\Services\Internal\Update\AccountUpdateService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    private Account              $account;
    private array                $accountInformation;
    private TransactionCurrency  $currency;
    private ?TransactionCurrency $foreignCurrency = null;
    private TransactionJournal   $journal;
    private bool                 $reconciled;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reconciled         = false;
        $this->accountInformation = [];
    }

    /**
     * Create transaction with negative amount (for source accounts).
     *
     * @throws FireflyException
     */
    public function createNegative(string $amount, ?string $foreignAmount): Transaction
    {
        if ('' === $foreignAmount) {
            $foreignAmount = null;
        }
        if (null !== $foreignAmount) {
            $foreignAmount = app('steam')->negative($foreignAmount);
        }

        return $this->create(app('steam')->negative($amount), $foreignAmount);
    }

    /**
     * @throws FireflyException
     */
    private function create(string $amount, ?string $foreignAmount): Transaction
    {
        if ('' === $foreignAmount) {
            $foreignAmount = null;
        }
        $data = [
            'reconciled'              => $this->reconciled,
            'account_id'              => $this->account->id,
            'transaction_journal_id'  => $this->journal->id,
            'description'             => null,
            'transaction_currency_id' => $this->currency->id,
            'amount'                  => $amount,
            'foreign_amount'          => null,
            'foreign_currency_id'     => null,
            'identifier'              => 0,
        ];

        try {
            /** @var null|Transaction $result */
            $result = Transaction::create($data);
        } catch (QueryException $e) {
            app('log')->error(sprintf('Could not create transaction: %s', $e->getMessage()), $data);
            app('log')->error($e->getMessage());
            app('log')->error($e->getTraceAsString());

            throw new FireflyException(sprintf('Query exception when creating transaction: %s', $e->getMessage()), 0, $e);
        }
        if (null === $result) {
            throw new FireflyException('Transaction is NULL.');
        }

        app('log')->debug(
            sprintf(
                'Created transaction #%d (%s %s, account %s), part of journal #%d',
                $result->id,
                $this->currency->code,
                $amount,
                $this->account->name,
                $this->journal->id
            )
        );

        // do foreign currency thing: add foreign currency info to $one and $two if necessary.
        if (null !== $this->foreignCurrency
            && null !== $foreignAmount
            && $this->foreignCurrency->id !== $this->currency->id) {
            $result->foreign_currency_id = $this->foreignCurrency->id;
            $result->foreign_amount      = $foreignAmount;
        }
        $result->save();

        // if present, update account with relevant account information from the array
        $this->updateAccountInformation();

        return $result;
    }

    /**
     * @throws FireflyException
     */
    private function updateAccountInformation(): void
    {
        if (!array_key_exists('iban', $this->accountInformation)) {
            app('log')->debug('No IBAN information in array, will not update.');

            return;
        }
        if ('' !== (string) $this->account->iban) {
            app('log')->debug('Account already has IBAN information, will not update.');

            return;
        }
        if ($this->account->iban === $this->accountInformation['iban']) {
            app('log')->debug('Account already has this IBAN, will not update.');

            return;
        }
        // validate info:
        $validator = Validator::make(['iban' => $this->accountInformation['iban']], [
            'iban' => ['required', new UniqueIban($this->account, $this->account->accountType->type)],
        ]);
        if ($validator->fails()) {
            app('log')->debug('Invalid or non-unique IBAN, will not update.');

            return;
        }

        app('log')->debug('Will update account with IBAN information.');
        $service   = app(AccountUpdateService::class);
        $service->update($this->account, ['iban' => $this->accountInformation['iban']]);
    }

    /**
     * Create transaction with positive amount (for destination accounts).
     *
     * @throws FireflyException
     */
    public function createPositive(string $amount, ?string $foreignAmount): Transaction
    {
        if ('' === $foreignAmount) {
            $foreignAmount = null;
        }
        if (null !== $foreignAmount) {
            $foreignAmount = app('steam')->positive($foreignAmount);
        }

        return $this->create(app('steam')->positive($amount), $foreignAmount);
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function setAccountInformation(array $accountInformation): void
    {
        $this->accountInformation = $accountInformation;
    }

    public function setCurrency(TransactionCurrency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @param null|TransactionCurrency $foreignCurrency |null
     */
    public function setForeignCurrency(?TransactionCurrency $foreignCurrency): void
    {
        $this->foreignCurrency = $foreignCurrency;
    }

    public function setJournal(TransactionJournal $journal): void
    {
        $this->journal = $journal;
    }

    public function setReconciled(bool $reconciled): void
    {
        $this->reconciled = $reconciled;
    }
}
