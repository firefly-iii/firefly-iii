<?php

/**
 * RecurrenceUpdateService.php
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

namespace FireflyIII\Services\Internal\Update;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\Note;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Services\Internal\Support\RecurringTransactionTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;

/**
 * Class RecurrenceUpdateService
 */
class RecurrenceUpdateService
{
    use RecurringTransactionTrait;
    use TransactionTypeTrait;

    private User $user;

    /**
     * Updates a recurrence.
     *
     * TODO if the user updates the type, the accounts must be validated again.
     *
     * @throws FireflyException
     */
    public function update(Recurrence $recurrence, array $data): Recurrence
    {
        $this->user = $recurrence->user;
        // update basic fields first:

        if (array_key_exists('recurrence', $data)) {
            $info = $data['recurrence'];
            if (array_key_exists('title', $info)) {
                $recurrence->title = $info['title'];
            }
            if (array_key_exists('description', $info)) {
                $recurrence->description = $info['description'];
            }
            if (array_key_exists('first_date', $info)) {
                $recurrence->first_date    = $info['first_date'];
                $recurrence->first_date_tz = $info['first_date']?->format('e');
            }
            if (array_key_exists('repeat_until', $info)) {
                $recurrence->repeat_until    = $info['repeat_until'];
                $recurrence->repeat_until_tz = $info['repeat_until']?->format('e');
                $recurrence->repetitions     = 0;
            }
            if (array_key_exists('nr_of_repetitions', $info)) {
                if (0 !== (int) $info['nr_of_repetitions']) {
                    $recurrence->repeat_until = null;
                }
                $recurrence->repetitions = $info['nr_of_repetitions'];
            }
            if (array_key_exists('apply_rules', $info)) {
                $recurrence->apply_rules = $info['apply_rules'];
            }
            if (array_key_exists('active', $info)) {
                $recurrence->active = $info['active'];
            }
            // update all meta data:
            if (array_key_exists('notes', $info)) {
                $this->setNoteText($recurrence, $info['notes']);
            }
        }
        $recurrence->save();

        // update all repetitions
        if (array_key_exists('repetitions', $data)) {
            app('log')->debug('Will update repetitions array');
            // update each repetition or throw error yay
            $this->updateRepetitions($recurrence, $data['repetitions'] ?? []);
        }
        // update all transactions:
        // update all transactions (and associated meta-data)
        if (array_key_exists('transactions', $data)) {
            $this->updateTransactions($recurrence, $data['transactions'] ?? []);
        }

        return $recurrence;
    }

    private function setNoteText(Recurrence $recurrence, string $text): void
    {
        $dbNote = $recurrence->notes()->first();
        if ('' !== $text) {
            if (null === $dbNote) {
                $dbNote = new Note();
                $dbNote->noteable()->associate($recurrence);
            }
            $dbNote->text = trim($text);
            $dbNote->save();

            return;
        }
        $dbNote?->delete();
    }

    /**
     * @throws FireflyException
     */
    private function updateRepetitions(Recurrence $recurrence, array $repetitions): void
    {
        $originalCount = $recurrence->recurrenceRepetitions()->count();
        if (0 === count($repetitions)) {
            // won't drop repetition, rather avoid.
            return;
        }
        // user added or removed repetitions, delete all and recreate:
        if ($originalCount !== count($repetitions)) {
            app('log')->debug('Delete existing repetitions and create new ones.');
            $this->deleteRepetitions($recurrence);
            $this->createRepetitions($recurrence, $repetitions);

            return;
        }
        // loop all and try to match them:
        app('log')->debug('Loop and find');
        foreach ($repetitions as $current) {
            $match  = $this->matchRepetition($recurrence, $current);
            if (null === $match) {
                throw new FireflyException('Cannot match recurring repetition to existing repetition. Not sure what to do. Break.');
            }
            $fields = [
                'type'    => 'repetition_type',
                'moment'  => 'repetition_moment',
                'skip'    => 'repetition_skip',
                'weekend' => 'weekend',
            ];
            foreach ($fields as $field => $column) {
                if (array_key_exists($field, $current)) {
                    $match->{$column} = $current[$field];
                    $match->save();
                }
            }
        }
    }

    private function matchRepetition(Recurrence $recurrence, array $data): ?RecurrenceRepetition
    {
        $originalCount = $recurrence->recurrenceRepetitions()->count();
        if (1 === $originalCount) {
            app('log')->debug('Return the first one');

            // @var RecurrenceRepetition|null
            return $recurrence->recurrenceRepetitions()->first();
        }
        // find it:
        $fields        = [
            'id'      => 'id',
            'type'    => 'repetition_type',
            'moment'  => 'repetition_moment',
            'skip'    => 'repetition_skip',
            'weekend' => 'weekend',
        ];
        $query         = $recurrence->recurrenceRepetitions();
        foreach ($fields as $field => $column) {
            if (array_key_exists($field, $data)) {
                $query->where($column, $data[$field]);
            }
        }

        // @var RecurrenceRepetition|null
        return $query->first();
    }

    /**
     * TODO this method is very complex.
     *
     * @throws FireflyException
     */
    private function updateTransactions(Recurrence $recurrence, array $transactions): void
    {
        app('log')->debug('Now in updateTransactions()');
        $originalCount        = $recurrence->recurrenceTransactions()->count();
        app('log')->debug(sprintf('Original count is %d', $originalCount));
        if (0 === count($transactions)) {
            // won't drop transactions, rather avoid.
            app('log')->warning('No transactions to update, too scared to continue!');

            return;
        }
        $combinations         = [];
        $originalTransactions = $recurrence->recurrenceTransactions()->get()->toArray();
        // First, make sure to loop all existing transactions and match them to a counterpart in the submitted transactions array.
        foreach ($originalTransactions as $i => $originalTransaction) {
            foreach ($transactions as $ii => $submittedTransaction) {
                if (array_key_exists('id', $submittedTransaction) && (int) $originalTransaction['id'] === (int) $submittedTransaction['id']) {
                    app('log')->debug(sprintf('Match original transaction #%d with an entry in the submitted array.', $originalTransaction['id']));
                    $combinations[] = [
                        'original'  => $originalTransaction,
                        'submitted' => $submittedTransaction,
                    ];
                    unset($originalTransactions[$i], $transactions[$ii]);
                }
            }
        }
        // If one left of both we can match those as well and presto.
        if (1 === count($originalTransactions) && 1 === count($transactions)) {
            $first          = array_shift($originalTransactions);
            app('log')->debug(sprintf('One left of each, link them (ID is #%d)', $first['id']));
            $combinations[] = [
                'original'  => $first,
                'submitted' => array_shift($transactions),
            ];
            unset($first);
        }
        // if they are both empty, we can safely loop all combinations and update them.
        if (0 === count($originalTransactions) && 0 === count($transactions)) {
            foreach ($combinations as $combination) {
                $this->updateCombination($recurrence, $combination);
            }
        }
        // anything left in the original transactions array can be deleted.
        foreach ($originalTransactions as $original) {
            app('log')->debug(sprintf('Original transaction #%d is unmatched, delete it!', $original['id']));
            $this->deleteTransaction($recurrence, (int) $original['id']);
        }
        // anything left is new.
        $this->createTransactions($recurrence, $transactions);
    }

    /**
     * It's a complex method but nothing surprising.
     *
     * @SuppressWarnings("PHPMD.NPathComplexity")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function updateCombination(Recurrence $recurrence, array $combination): void
    {
        $original        = $combination['original'];
        $submitted       = $combination['submitted'];
        $currencyFactory = app(TransactionCurrencyFactory::class);

        /** @var RecurrenceTransaction $transaction */
        $transaction     = $recurrence->recurrenceTransactions()->find($original['id']);
        app('log')->debug(sprintf('Now in updateCombination(#%d)', $original['id']));

        // loop all and try to match them:
        $currency        = null;
        $foreignCurrency = null;
        if (array_key_exists('currency_id', $submitted) || array_key_exists('currency_code', $submitted)) {
            $currency = $currencyFactory->find(
                array_key_exists('currency_id', $submitted) ? (int) $submitted['currency_id'] : null,
                array_key_exists('currency_code', $submitted) ? $submitted['currency_code'] : null
            );
        }
        if (null === $currency) {
            unset($submitted['currency_id'], $submitted['currency_code']);
        }
        if (null !== $currency) {
            $submitted['currency_id'] = $currency->id;
        }
        if (array_key_exists('foreign_currency_id', $submitted) || array_key_exists('foreign_currency_code', $submitted)) {
            $foreignCurrency = $currencyFactory->find(
                array_key_exists('foreign_currency_id', $submitted) ? (int) $submitted['foreign_currency_id'] : null,
                array_key_exists('foreign_currency_code', $submitted) ? $submitted['foreign_currency_code'] : null
            );
        }
        if (null === $foreignCurrency) {
            unset($submitted['foreign_currency_id'], $currency['foreign_currency_code']);
        }
        if (null !== $foreignCurrency) {
            $submitted['foreign_currency_id'] = $foreignCurrency->id;
        }

        // update fields that are part of the recurring transaction itself.
        $fields          = [
            'source_id'           => 'source_id',
            'destination_id'      => 'destination_id',
            'amount'              => 'amount',
            'foreign_amount'      => 'foreign_amount',
            'description'         => 'description',
            'currency_id'         => 'transaction_currency_id',
            'foreign_currency_id' => 'foreign_currency_id',
        ];
        foreach ($fields as $field => $column) {
            if (array_key_exists($field, $submitted)) {
                $transaction->{$column} = $submitted[$field];
                $transaction->save();
            }
        }
        // update meta data
        if (array_key_exists('budget_id', $submitted)) {
            $this->setBudget($transaction, (int) $submitted['budget_id']);
        }
        if (array_key_exists('bill_id', $submitted)) {
            $this->setBill($transaction, (int) $submitted['bill_id']);
        }
        // reset category if name is set but empty:
        // can be removed when v1 is retired.
        if (array_key_exists('category_name', $submitted) && '' === (string) $submitted['category_name']) {
            app('log')->debug('Category name is submitted but is empty. Set category to be empty.');
            $submitted['category_name'] = null;
            $submitted['category_id']   = 0;
        }

        if (array_key_exists('category_id', $submitted)) {
            app('log')->debug(sprintf('Category ID is submitted, set category to be %d.', (int) $submitted['category_id']));
            $this->setCategory($transaction, (int) $submitted['category_id']);
        }

        if (array_key_exists('tags', $submitted) && is_array($submitted['tags'])) {
            $this->updateTags($transaction, $submitted['tags']);
        }
        if (array_key_exists('piggy_bank_id', $submitted)) {
            $this->updatePiggyBank($transaction, (int) $submitted['piggy_bank_id']);
        }
    }

    private function deleteTransaction(Recurrence $recurrence, int $transactionId): void
    {
        app('log')->debug(sprintf('Will delete transaction #%d in recurrence #%d.', $transactionId, $recurrence->id));
        $recurrence->recurrenceTransactions()->where('id', $transactionId)->delete();
    }
}
