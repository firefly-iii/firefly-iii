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
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Support\JournalServiceTrait;
use Illuminate\Support\Collection;
use Log;

/**
 * Class to centralise code that updates a journal given the input by system.
 *
 * Class JournalUpdateService
 */
class JournalUpdateService
{
    use JournalServiceTrait;

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
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     * @throws \FireflyIII\Exceptions\FireflyException
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
        $service->setUser($journal->user);

        // create transactions:
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($journal->user);

        Log::debug(sprintf('Found %d rows in array (should result in %d transactions', \count($data['transactions']), \count($data['transactions']) * 2));

        /**
         * @var int   $identifier
         * @var array $trData
         */
        foreach ($data['transactions'] as $identifier => $trData) {
            // exists transaction(s) with this identifier? update!
            /** @var Collection $existing */
            $existing = $journal->transactions()->where('identifier', $identifier)->get();
            Log::debug(sprintf('Found %d transactions with identifier %d', $existing->count(), $identifier));
            if ($existing->count() > 0) {
                $existing->each(
                    function (Transaction $transaction) use ($service, $trData) {
                        Log::debug(sprintf('Update transaction #%d (identifier %d)', $transaction->id, $trData['identifier']));
                        $service->update($transaction, $trData);
                    }
                );
                continue;
            }
            Log::debug('Found none, so create a pair.');
            // otherwise, create!
            $factory->createPair($journal, $trData);
        }
        // could be that journal has more transactions than submitted (remove split)
        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        Log::debug(sprintf('Journal #%d has %d transactions', $journal->id, $transactions->count()));
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            Log::debug(sprintf('Now at transaction %d with identifier %d', $transaction->id, $transaction->identifier));
            if (!isset($data['transactions'][$transaction->identifier])) {
                Log::debug('No such entry in array, delete this set of transactions.');
                $journal->transactions()->where('identifier', $transaction->identifier)->delete();
            }
        }
        Log::debug(sprintf('New count is %d, transactions array held %d items', $journal->transactions()->count(), \count($data['transactions'])));

        // connect bill:
        $this->connectBill($journal, $data);

        // connect tags:
        $this->connectTags($journal, $data);

        // remove category from journal:
        $journal->categories()->sync([]);

        // remove budgets from journal:
        $journal->budgets()->sync([]);

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
        $service->setUser($journal->user);
        if (TransactionType::WITHDRAWAL === $journal->transactionType->type) {
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                $service->updateBudget($transaction, $budgetId);
            }

            return $journal;
        }
        // clear budget.
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $transaction->budgets()->sync([]);
        }
        // remove budgets from journal:
        $journal->budgets()->sync([]);

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
        $service->setUser($journal->user);

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $service->updateCategory($transaction, $category);
        }
        // make journal empty:
        $journal->categories()->sync([]);

        return $journal;
    }

}
