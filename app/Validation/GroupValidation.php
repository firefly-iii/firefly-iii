<?php

/**
 * GroupValidation.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Validation;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use Illuminate\Validation\Validator;

/**
 * Trait GroupValidation.
 *
 * This trait combines some of the validation methods used to validate if journal and group data is submitted correctly.
 */
trait GroupValidation
{
    /**
     * A catch when users submit splits with no source or destination info at all.
     *
     * TODO This should prevent errors down the road but I'm not yet sure what I'm validating here
     * TODO so I disabled this on 2023-10-22 to see if it causes any issues.
     *
     * @throws FireflyException
     */
    protected function preventNoAccountInfo(Validator $validator): void
    {
        $transactions = $this->getTransactionsArray($validator);
        $keys         = [
            'source_id',
            'destination_id',
            'source_name',
            'destination_name',
            'source_iban',
            'destination_iban',
            'source_number',
            'destination_number',
        ];

        /** @var null|array $transaction */
        foreach ($transactions as $index => $transaction) {
            if (!is_array($transaction)) {
                throw new FireflyException('Invalid data submitted: transaction is not array.');
            }
            $hasAccountInfo = false;
            $hasJournalId   = array_key_exists('transaction_journal_id', $transaction);
            foreach ($keys as $key) {
                if (array_key_exists($key, $transaction) && '' !== (string)$transaction[$key]) {
                    $hasAccountInfo = true;
                }
            }
            // set errors:
            if (false === $hasAccountInfo && !$hasJournalId) {
                $validator->errors()->add(
                    sprintf('transactions.%d.source_id', $index),
                    (string)trans('validation.generic_no_source')
                );
                $validator->errors()->add(
                    sprintf('transactions.%d.destination_id', $index),
                    (string)trans('validation.generic_no_destination')
                );
            }
        }
        // only an issue if there is no transaction_journal_id
    }

    abstract protected function getTransactionsArray(Validator $validator): array;

    protected function preventUpdateReconciled(Validator $validator, TransactionGroup $transactionGroup): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));

        $count     = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', 'transactions.transaction_journal_id')
            ->leftJoin('transaction_groups', 'transaction_groups.id', 'transaction_journals.transaction_group_id')
            ->where('transaction_journals.transaction_group_id', $transactionGroup->id)
            ->where('transactions.reconciled', 1)->where('transactions.amount', '<', 0)->count('transactions.id')
        ;
        if (0 === $count) {
            app('log')->debug(sprintf('Transaction is not reconciled, done with %s', __METHOD__));

            return;
        }
        $data      = $validator->getData();
        $forbidden = ['amount', 'foreign_amount', 'currency_code', 'currency_id', 'foreign_currency_code', 'foreign_currency_id',
            'source_id', 'source_name', 'source_number', 'source_iban',
            'destination_id', 'destination_name', 'destination_number', 'destination_iban',
        ];

        // stop protesting when reconciliation is set to FALSE.

        foreach ($data['transactions'] as $index => $row) {
            if (false === ($row['reconciled'] ?? false)) {
                continue;
            }
            foreach ($forbidden as $key) {
                if (array_key_exists($key, $row)) {
                    $validator->errors()->add(
                        sprintf('transactions.%d.%s', $index, $key),
                        (string)trans('validation.reconciled_forbidden_field', ['field' => $key])
                    );
                }
            }
        }

        app('log')->debug(sprintf('Done with %s', __METHOD__));
    }

    /**
     * Adds an error to the "description" field when the user has submitted no descriptions and no
     * journal description.
     */
    protected function validateDescriptions(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        app('log')->debug('Now in GroupValidation::validateDescriptions()');
        $transactions      = $this->getTransactionsArray($validator);
        $validDescriptions = 0;
        foreach ($transactions as $transaction) {
            if ('' !== (string)($transaction['description'] ?? null)) {
                ++$validDescriptions;
            }
        }

        // no valid descriptions?
        if (0 === $validDescriptions) {
            $validator->errors()->add(
                'transactions.0.description',
                (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );
        }
    }

    protected function validateGroupDescription(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        app('log')->debug('Now in validateGroupDescription()');
        $data         = $validator->getData();
        $transactions = $this->getTransactionsArray($validator);

        $groupTitle   = $data['group_title'] ?? '';
        if ('' === $groupTitle && count($transactions) > 1) {
            $validator->errors()->add('group_title', (string)trans('validation.group_title_mandatory'));
        }
    }

    /**
     * This method validates if the user has submitted transaction journal ID's for each array they submit, if they've
     * submitted more than 1 transaction journal. This check is necessary because Firefly III isn't able to distinguish
     * between journals without the ID.
     */
    protected function validateJournalIds(Validator $validator, TransactionGroup $transactionGroup): void
    {
        app('log')->debug(sprintf('Now in GroupValidation::validateJournalIds(%d)', $transactionGroup->id));
        $transactions = $this->getTransactionsArray($validator);

        if (count($transactions) < 2) {
            // no need for validation.
            app('log')->debug(sprintf('%d transaction(s) in submission, can skip this check.', count($transactions)));

            return;
        }

        // check each array:
        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($transactions as $index => $transaction) {
            $this->validateJournalId($validator, $index, $transaction, $transactionGroup);
        }
    }

    /**
     * Do the validation required by validateJournalIds.
     */
    private function validateJournalId(Validator $validator, int $index, array $transaction, TransactionGroup $transactionGroup): void
    {
        $journalId = 0;
        if (array_key_exists('transaction_journal_id', $transaction)) {
            $journalId = $transaction['transaction_journal_id'];
        }
        app('log')->debug(sprintf('Now in validateJournalId(%d, %d)', $index, $journalId));
        if (0 === $journalId || '' === $journalId || '0' === $journalId) {
            app('log')->debug('Submitted 0, will accept to be used in a new transaction.');

            return;
        }
        $journalId = (int)$journalId;
        $count     = $transactionGroup->transactionJournals()->where('transaction_journals.id', $journalId)->count();
        if (0 === $journalId || 0 === $count) {
            app('log')->warning(sprintf('Transaction group #%d has %d journals with ID %d', $transactionGroup->id, $count, $journalId));
            app('log')->warning('Invalid submission: Each split must have transaction_journal_id (either valid ID or 0).');
            $validator->errors()->add(sprintf('transactions.%d.source_name', $index), (string)trans('validation.need_id_in_edit'));
        }
    }
}
