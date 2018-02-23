<?php
/**
 * JournalUpdateService.php
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

use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class to centralise code that updates a journal given the input by system.
 *
 * Class JournalUpdateService
 */
class JournalUpdateService
{
    use JournalServiceTrait;
    /** @var User */
    private $user;

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \Exception
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal
    {
        // update journal:
        $journal->description = $data['description'];
        $journal->date        = $data['date'];
        $journal->save();

        // update transactions:
        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user);

        // create transactions
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($this->user);

        /**
         * @var int   $identifier
         * @var array $trData
         */
        foreach ($data['transactions'] as $identifier => $trData) {
            // exists transaction(s) with this identifier? update!
            /** @var Collection $existing */
            $existing = $journal->transactions()->where('identifier', $identifier)->get();
            if ($existing->count() > 0) {
                $existing->each(
                    function (Transaction $transaction) use ($service, $trData) {
                        $service->update($transaction, $trData);
                    }
                );
                continue;
            }
            // otherwise, create!
            $factory->createPair($journal, $trData);
        }
        // could be that journal has more transactions than submitted (remove split)
        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            if (!isset($data['transactions'][$transaction->identifier])) {
                $journal->transactions()->where('identifier', $transaction->identifier)->delete();
            }
        }

        // connect bill:
        $this->connectBill($journal, $data);

        // connect tags:
        $this->connectTags($journal, $data);

        // update or create custom fields:
        // store date meta fields (if present):
        $this->storeMeta($journal, $data, 'interest_date');
        $this->storeMeta($journal, $data, 'book_date');
        $this->storeMeta($journal, $data, 'process_date');
        $this->storeMeta($journal, $data, 'due_date');
        $this->storeMeta($journal, $data, 'payment_date');
        $this->storeMeta($journal, $data, 'invoice_date');
        $this->storeMeta($journal, $data, 'internal_reference');

        // store note:
        $this->storeNote($journal, $data['notes']);


        return $journal;
    }

    /**
     * Update budget for a journal.
     *
     * @param TransactionJournal $journal
     * @param int                $budgetId
     *
     * @return TransactionJournal
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal
    {
        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user);

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $service->updateBudget($transaction, $budgetId);
        }

        return $journal;
    }

    /**
     * Update category for a journal.
     *
     * @param TransactionJournal $journal
     * @param string             $category
     *
     * @return TransactionJournal
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal
    {
        /** @var TransactionUpdateService $service */
        $service = app(TransactionUpdateService::class);
        $service->setUser($this->user);

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $service->updateCategory($transaction, $category);
        }

        return $journal;
    }

}