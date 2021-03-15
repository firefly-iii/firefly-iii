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

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Note;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Services\Internal\Support\RecurringTransactionTrait;
use FireflyIII\Services\Internal\Support\TransactionTypeTrait;
use FireflyIII\User;
use Log;

/**
 * Class RecurrenceUpdateService
 *
 * @codeCoverageIgnore
 */
class RecurrenceUpdateService
{
    use TransactionTypeTrait, RecurringTransactionTrait;

    private User $user;

    /**
     * Updates a recurrence.
     *
     * TODO if the user updates the type, accounts must be validated (again).
     *
     * @param Recurrence $recurrence
     * @param array      $data
     *
     * @return Recurrence
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
                $recurrence->first_date = $info['first_date'];
            }
            if (array_key_exists('repeat_until', $info)) {
                $recurrence->repeat_until = $info['repeat_until'];
                $recurrence->repetitions  = 0;
            }
            if (array_key_exists('nr_of_repetitions', $info)) {
                if (0 !== (int)$info['nr_of_repetitions']) {
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
            Log::debug('Will update repetitions array');
            // update each repetition or throw error yay
            $this->updateRepetitions($recurrence, $data['repetitions'] ?? []);
        }
        // update all transactions:


        // update all transactions (and associated meta-data)
        if (array_key_exists('transactions', $data)) {
            $this->updateTransactions($recurrence, $data['transactions'] ?? []);
            //            $this->deleteTransactions($recurrence);
            //            $this->createTransactions($recurrence, $data['transactions'] ?? []);
        }

        return $recurrence;
    }

    /**
     * TODO this method is way too complex.
     *
     * @param Recurrence $recurrence
     * @param array      $transactions
     *
     * @throws FireflyException
     */
    private function updateTransactions(Recurrence $recurrence, array $transactions): void
    {
        $originalCount = $recurrence->recurrenceTransactions()->count();
        if (0 === count($transactions)) {
            // wont drop transactions, rather avoid.
            return;
        }
        // user added or removed repetitions, delete all and recreate:
        if ($originalCount !== count($transactions)) {
            Log::debug('Del + recreate');
            $this->deleteTransactions($recurrence);
            $this->createTransactions($recurrence, $transactions);

            return;
        }
        // loop all and try to match them:
        if ($originalCount === count($transactions)) {
            Log::debug('Loop and find');
            foreach ($transactions as $current) {
                $match = $this->matchTransaction($recurrence, $current);
                if (null === $match) {
                    throw new FireflyException('Cannot match recurring transaction to existing transaction. Not sure what to do. Break.');
                }
                // TODO find currency
                // TODO find foreign currency

                // update fields
                $fields = [
                    'source_id'      => 'source_id',
                    'destination_id' => 'destination_id',
                    'amount'         => 'amount',
                    'foreign_amount' => 'foreign_amount',
                    'description'    => 'description',
                ];
                foreach ($fields as $field => $column) {
                    if (array_key_exists($field, $current)) {
                        $match->$column = $current[$field];
                        $match->save();
                    }
                }
                // update meta data
                // budget_id
                // category_id
                // tags
                // piggy_bank_id
            }
        }
    }

    /**
     * @param Recurrence $recurrence
     * @param string     $text
     */
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
        if (null !== $dbNote && '' === $text) {
            try {
                $dbNote->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Could not delete note: %s', $e->getMessage()));
            }
        }

    }

    /**
     *
     * @param Recurrence $recurrence
     * @param array      $repetitions
     */
    private function updateRepetitions(Recurrence $recurrence, array $repetitions): void
    {
        $originalCount = $recurrence->recurrenceRepetitions()->count();
        if (0 === count($repetitions)) {
            // wont drop repetition, rather avoid.
            return;
        }
        // user added or removed repetitions, delete all and recreate:
        if ($originalCount !== count($repetitions)) {
            Log::debug('Del + recreate');
            $this->deleteRepetitions($recurrence);
            $this->createRepetitions($recurrence, $repetitions);

            return;
        }
        // loop all and try to match them:
        if ($originalCount === count($repetitions)) {
            Log::debug('Loop and find');
            foreach ($repetitions as $current) {
                $match = $this->matchRepetition($recurrence, $current);
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
                        $match->$column = $current[$field];
                        $match->save();
                    }
                }
            }
        }
    }

    /**
     * @param array $data
     *
     * @return RecurrenceRepetition|null
     */
    private function matchRepetition(Recurrence $recurrence, array $data): ?RecurrenceRepetition
    {
        $originalCount = $recurrence->recurrenceRepetitions()->count();
        if (1 === $originalCount) {
            Log::debug('Return the first one');

            return $recurrence->recurrenceRepetitions()->first();
        }
        // find it:
        $fields = ['id'      => 'id',
                   'type'    => 'repetition_type',
                   'moment'  => 'repetition_moment',
                   'skip'    => 'repetition_skip',
                   'weekend' => 'weekend',
        ];
        $query  = $recurrence->recurrenceRepetitions();
        foreach ($fields as $field => $column) {
            if (array_key_exists($field, $data)) {
                $query->where($column, $data[$field]);
            }
        }

        return $query->first();
    }

    /**
     * @param array $data
     *
     * @return RecurrenceTransaction|null
     */
    private function matchTransaction(Recurrence $recurrence, array $data): ?RecurrenceTransaction
    {
        $originalCount = $recurrence->recurrenceTransactions()->count();
        if (1 === $originalCount) {
            Log::debug('Return the first one');

            return $recurrence->recurrenceTransactions()->first();
        }
        // find it based on data
        $fields = [
            'id'                  => 'id',
            'currency_id'         => 'transaction_currency_id',
            'foreign_currency_id' => 'foreign_currency_id',
            'source_id'           => 'source_id',
            'destination_id'      => 'destination_id',
            'amount'              => 'amount',
            'foreign_amount'      => 'foreign_amount',
            'description'         => 'description',
        ];
        $query  = $recurrence->recurrenceTransactions();
        foreach ($fields as $field => $column) {
            if (array_key_exists($field, $data)) {
                $query->where($column, $data[$field]);
            }
        }

        return $query->first();
    }
}
