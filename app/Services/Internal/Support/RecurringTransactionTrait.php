<?php
/**
 * RecurringTransactionTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceMeta;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\RecurrenceTransactionMeta;
use FireflyIII\Models\TransactionType;
use Log;


/**
 * Trait RecurringTransactionTrait
 *
 * @package FireflyIII\Services\Internal\Support
 */
trait RecurringTransactionTrait
{
    /**
     * @param Recurrence $recurrence
     * @param array      $repetitions
     */
    public function createRepetitions(Recurrence $recurrence, array $repetitions): void
    {
        /** @var array $array */
        foreach ($repetitions as $array) {
            RecurrenceRepetition::create(
                [
                    'recurrence_id'     => $recurrence->id,
                    'repetition_type'   => $array['type'],
                    'repetition_moment' => $array['moment'],
                    'repetition_skip'   => $array['skip'],
                ]
            );

        }
    }

    /**
     * @param Recurrence $recurrence
     * @param array      $transactions
     *
     * @throws FireflyException
     */
    public function createTransactions(Recurrence $recurrence, array $transactions): void
    {

        foreach ($transactions as $array) {
            $source      = null;
            $destination = null;
            switch ($recurrence->transactionType->type) {
                default:
                    throw new FireflyException(sprintf('Cannot create "%s".', $recurrence->transactionType->type));
                case TransactionType::WITHDRAWAL:
                    $source      = $this->findAccount(AccountType::ASSET, $array['source_account_id'], null);
                    $destination = $this->findAccount(AccountType::EXPENSE, null, $array['destination_account_name']);
                    break;
                case TransactionType::DEPOSIT:
                    $source      = $this->findAccount(AccountType::REVENUE, null, $array['source_account_name']);
                    $destination = $this->findAccount(AccountType::ASSET, $array['destination_account_id'], null);
                    break;
                case TransactionType::TRANSFER:
                    $source      = $this->findAccount(AccountType::ASSET, $array['source_account_id'], null);
                    $destination = $this->findAccount(AccountType::ASSET, $array['destination_account_id'], null);
                    break;
            }

            $transaction = new RecurrenceTransaction(
                [
                    'recurrence_id'           => $recurrence->id,
                    'transaction_currency_id' => $array['transaction_currency_id'],
                    'foreign_currency_id'     => '' === (string)$array['foreign_amount'] ? null : $array['foreign_currency_id'],
                    'source_account_id'       => $source->id,
                    'destination_account_id'  => $destination->id,
                    'amount'                  => $array['amount'],
                    'foreign_amount'          => '' === (string)$array['foreign_amount'] ? null : (string)$array['foreign_amount'],
                    'description'             => $array['description'],
                ]
            );
            $transaction->save();

            // create recurrence transaction meta:
            if ($array['budget_id'] > 0) {
                RecurrenceTransactionMeta::create(
                    [
                        'rt_id' => $transaction->id,
                        'name'  => 'budget_id',
                        'value' => $array['budget_id'],
                    ]
                );
            }
            if ('' !== (string)$array['category_name']) {
                RecurrenceTransactionMeta::create(
                    [
                        'rt_id' => $transaction->id,
                        'name'  => 'category_name',
                        'value' => $array['category_name'],
                    ]
                );
            }
        }
    }

    /**
     * @param Recurrence $recurrence
     */
    public function deleteRepetitions(Recurrence $recurrence): void
    {
        $recurrence->recurrenceRepetitions()->delete();
    }

    /**
     * @param Recurrence $recurrence
     */
    public function deleteTransactions(Recurrence $recurrence): void
    {
        /** @var RecurrenceTransaction $transaction */
        foreach ($recurrence->recurrenceTransactions as $transaction) {
            $transaction->recurrenceTransactionMeta()->delete();
            try {
                $transaction->delete();
            } catch (Exception $e) {
                Log::debug($e->getMessage());
            }
        }
    }

    /**
     * @param Recurrence $recurrence
     * @param array      $data
     */
    public function updateMetaData(Recurrence $recurrence, array $data): void
    {
        // only two special meta fields right now. Let's just hard code them.
        $piggyId = (int)($data['meta']['piggy_bank_id'] ?? 0.0);
        if ($piggyId > 0) {
            /** @var RecurrenceMeta $entry */
            $entry = $recurrence->recurrenceMeta()->where('name', 'piggy_bank_id')->first();
            if (null === $entry) {
                $entry = RecurrenceMeta::create(['recurrence_id' => $recurrence->id, 'name' => 'piggy_bank_id', 'value' => $piggyId]);
            }
            $entry->value = $piggyId;
            $entry->save();
        }
        if ($piggyId === 0) {
            // delete if present
            $recurrence->recurrenceMeta()->where('name', 'piggy_bank_id')->delete();
        }
        $tags = $data['meta']['tags'] ?? [];
        if (\count($tags) > 0) {
            /** @var RecurrenceMeta $entry */
            $entry = $recurrence->recurrenceMeta()->where('name', 'tags')->first();
            if (null === $entry) {
                $entry = RecurrenceMeta::create(['recurrence_id' => $recurrence->id, 'name' => 'tags', 'value' => implode(',', $tags)]);
            }
            $entry->value = implode(',', $tags);
            $entry->save();
        }
        if (\count($tags) === 0) {
            // delete if present
            $recurrence->recurrenceMeta()->where('name', 'tags')->delete();
        }
    }
}