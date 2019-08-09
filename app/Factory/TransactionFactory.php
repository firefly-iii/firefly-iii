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


use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    /** @var TransactionJournal */
    private $journal;
    /** @var Account */
    private $account;
    /** @var TransactionCurrency */
    private $currency;
    /** @var TransactionCurrency */
    private $foreignCurrency;
    /** @var User */
    private $user;
    /** @var bool */
    private $reconciled;

    /**
     * Constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
        $this->reconciled = false;
    }

    /**
     * @param bool $reconciled
     * @codeCoverageIgnore
     */
    public function setReconciled(bool $reconciled): void
    {
        $this->reconciled = $reconciled;
    }

    /**
     * @param Account $account
     * @codeCoverageIgnore
     */
    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @param TransactionCurrency $currency
     * @codeCoverageIgnore
     */
    public function setCurrency(TransactionCurrency $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @param TransactionCurrency $foreignCurrency |null
     * @codeCoverageIgnore
     */
    public function setForeignCurrency(?TransactionCurrency $foreignCurrency): void
    {
        $this->foreignCurrency = $foreignCurrency;
    }

    /**
     * Create transaction with negative amount (for source accounts).
     *
     * @param string $amount
     * @param string|null $foreignAmount
     * @return Transaction|null
     */
    public function createNegative(string $amount, ?string $foreignAmount): ?Transaction
    {
        if (null !== $foreignAmount) {
            $foreignAmount = app('steam')->negative($foreignAmount);
        }

        return $this->create(app('steam')->negative($amount), $foreignAmount);
    }

    /**
     * Create transaction with positive amount (for destination accounts).
     *
     * @param string $amount
     * @param string|null $foreignAmount
     * @return Transaction|null
     */
    public function createPositive(string $amount, ?string $foreignAmount): ?Transaction
    {
        if (null !== $foreignAmount) {
            $foreignAmount = app('steam')->positive($foreignAmount);
        }

        return $this->create(app('steam')->positive($amount), $foreignAmount);
    }


    /**
     * @param TransactionJournal $journal
     * @codeCoverageIgnore
     */
    public function setJournal(TransactionJournal $journal): void
    {
        $this->journal = $journal;
    }

    /**
     * @param User $user
     * @codeCoverageIgnore
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param string $amount
     * @param string|null $foreignAmount
     * @return Transaction|null
     */
    private function create(string $amount, ?string $foreignAmount): ?Transaction
    {
        $result = null;
        $data   = [
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
            $result = Transaction::create($data);
            // @codeCoverageIgnoreStart
        } catch (QueryException $e) {
            Log::error(sprintf('Could not create transaction: %s', $e->getMessage()), $data);
        }
        // @codeCoverageIgnoreEnd
        if (null !== $result) {
            Log::debug(
                sprintf(
                    'Created transaction #%d (%s %s, account %s), part of journal #%d', $result->id, $this->currency->code, $amount, $this->account->name,
                    $this->journal->id
                )
            );

            // do foreign currency thing: add foreign currency info to $one and $two if necessary.
            if (null !== $this->foreignCurrency && null !== $foreignAmount && $this->foreignCurrency->id !== $this->currency->id) {
                $result->foreign_currency_id = $this->foreignCurrency->id;
                $result->foreign_amount      = $foreignAmount;

            }
            $result->save();
        }

        return $result;
    }
}
