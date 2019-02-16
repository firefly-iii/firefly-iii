<?php
/**
 * TransactionUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Models\Transaction;
use FireflyIII\Services\Internal\Support\TransactionServiceTrait;
use FireflyIII\User;
use Log;

/**
 * Class TransactionUpdateService
 */
class TransactionUpdateService
{
    use TransactionServiceTrait;

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
    }

    /**
     * @param int $transactionId
     *
     * @return Transaction|null
     */
    public function reconcile(int $transactionId): ?Transaction
    {
        $transaction = Transaction::find($transactionId);
        if (null !== $transaction) {
            $transaction->reconciled = true;
            $transaction->save();

            return $transaction;
        }

        return null;

    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Transaction $transaction
     * @param array       $data
     *
     * @return Transaction
     * @throws \FireflyIII\Exceptions\FireflyException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $currency = $this->findCurrency($data['currency_id'], $data['currency_code']);
        $journal  = $transaction->transactionJournal;
        $amount   = (string)$data['amount'];
        $account  = null;
        // update description:
        $transaction->description = $data['description'];
        $foreignAmount            = null;
        if ((float)$transaction->amount < 0) {
            // this is the source transaction.
            $type          = $this->accountType($journal, 'source');
            $account       = $this->findAccount($type, $data['source_id'], $data['source_name']);
            $amount        = app('steam')->negative($amount);
            $foreignAmount = app('steam')->negative((string)$data['foreign_amount']);
        }

        if ((float)$transaction->amount > 0) {
            // this is the destination transaction.
            $type          = $this->accountType($journal, 'destination');
            $account       = $this->findAccount($type, $data['destination_id'], $data['destination_name']);
            $amount        = app('steam')->positive($amount);
            $foreignAmount = app('steam')->positive((string)$data['foreign_amount']);
        }

        // update the actual transaction:
        $transaction->description             = $data['description'];
        $transaction->amount                  = $amount;
        $transaction->foreign_amount          = null;
        $transaction->transaction_currency_id = null === $currency ? $transaction->transaction_currency_id : $currency->id;
        $transaction->account_id              = $account->id;
        $transaction->reconciled              = $data['reconciled'];
        $transaction->save();

        // set foreign currency
        $foreign = $this->findCurrency($data['foreign_currency_id'], $data['foreign_currency_code']);
        // set foreign amount:
        if (null !== $foreign && null !== $data['foreign_amount']) {
            $this->setForeignCurrency($transaction, $foreign);
            $this->setForeignAmount($transaction, $foreignAmount);
        }
        if (null === $foreign || null === $data['foreign_amount']) {
            $this->setForeignCurrency($transaction, null);
            $this->setForeignAmount($transaction, null);
        }

        // set budget:
        $budget = $this->findBudget($data['budget_id'], $data['budget_name']);
        $this->setBudget($transaction, $budget);

        // set category
        $category = $this->findCategory($data['category_id'], $data['category_name']);
        $this->setCategory($transaction, $category);

        return $transaction;
    }

    /**
     * Update budget for a journal.
     *
     * @param Transaction $transaction
     * @param int         $budgetId
     *
     * @return Transaction
     */
    public function updateBudget(Transaction $transaction, int $budgetId): Transaction
    {
        $budget = $this->findBudget($budgetId, null);
        $this->setBudget($transaction, $budget);

        return $transaction;

    }

    /**
     * Update category for a journal.
     *
     * @param Transaction $transaction
     * @param string      $category
     *
     * @return Transaction
     */
    public function updateCategory(Transaction $transaction, string $category): Transaction
    {
        $found = $this->findCategory(0, $category);
        $this->setCategory($transaction, $found);

        return $transaction;
    }
}
