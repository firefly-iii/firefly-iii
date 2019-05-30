<?php
/**
 * TransactionValidation.php
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

namespace FireflyIII\Validation;

use FireflyIII\Models\TransactionGroup;
use Illuminate\Validation\Validator;

/**
 * Trait TransactionValidation
 */
trait TransactionValidation
{
    /**
     * Validates the given account information. Switches on given transaction type.
     *
     * @param Validator $validator
     */
    public function validateAccountInformation(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];

        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);


        foreach ($transactions as $index => $transaction) {
            $transactionType = $transaction['type'] ?? 'invalid';
            $accountValidator->setTransactionType($transactionType);

            // validate source account.
            $sourceId    = isset($transaction['source_id']) ? (int)$transaction['source_id'] : null;
            $sourceName  = $transaction['source_name'] ?? null;
            $validSource = $accountValidator->validateSource($sourceId, $sourceName);

            // do something with result:
            if (false === $validSource) {
                $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);

                return;
            }
            // validate destination account
            $destinationId    = isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : null;
            $destinationName  = $transaction['destination_name'] ?? null;
            $validDestination = $accountValidator->validateDestination($destinationId, $destinationName);
            // do something with result:
            if (false === $validDestination) {
                $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
                $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);

                return;
            }
        }
    }

    /**
     * Adds an error to the "description" field when the user has submitted no descriptions and no
     * journal description.
     *
     * @param Validator $validator
     */
    public function validateDescriptions(Validator $validator): void
    {
        $data              = $validator->getData();
        $transactions      = $data['transactions'] ?? [];
        $validDescriptions = 0;
        foreach ($transactions as $index => $transaction) {
            if ('' !== (string)($transaction['description'] ?? null)) {
                $validDescriptions++;
            }
        }

        // no valid descriptions?
        if (0 === $validDescriptions) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );
        }
    }

    /**
     * If the transactions contain foreign amounts, there must also be foreign currency information.
     *
     * @param Validator $validator
     */
    public function validateForeignCurrencyInformation(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        foreach ($transactions as $index => $transaction) {
            // if foreign amount is present, then the currency must be as well.
            if (isset($transaction['foreign_amount']) && !(isset($transaction['foreign_currency_id']) || isset($transaction['foreign_currency_code']))) {
                $validator->errors()->add(
                    'transactions.' . $index . '.foreign_amount',
                    (string)trans('validation.require_currency_info')
                );
            }
            // if the currency is present, then the amount must be present as well.
            if ((isset($transaction['foreign_currency_id']) || isset($transaction['foreign_currency_code'])) && !isset($transaction['foreign_amount'])) {
                $validator->errors()->add(
                    'transactions.' . $index . '.foreign_amount',
                    (string)trans('validation.require_currency_amount')
                );
            }
        }
    }

    /**
     * @param Validator $validator
     */
    public function validateGroupDescription(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        $groupTitle   = $data['group_title'] ?? '';
        if ('' === $groupTitle && count($transactions) > 1) {
            $validator->errors()->add('group_title', (string)trans('validation.group_title_mandatory'));
        }
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     *
     * @param Validator $validator
     */
    public function validateOneTransaction(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        // need at least one transaction
        if (0 === count($transactions)) {
            $validator->errors()->add('transactions.0.description', (string)trans('validation.at_least_one_transaction'));
        }
    }

    /**
     * All types of splits must be equal.
     *
     * @param Validator $validator
     */
    public function validateTransactionTypes(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        $types        = [];
        foreach ($transactions as $index => $transaction) {
            $types[] = $transaction['type'] ?? 'invalid';
        }
        $unique = array_unique($types);
        if (count($unique) > 1) {
            $validator->errors()->add('transactions.0.type', (string)trans('validation.transaction_types_equal'));

            return;
        }
        $first = $unique[0] ?? 'invalid';
        if ('invalid' === $first) {
            $validator->errors()->add('transactions.0.type', (string)trans('validation.invalid_transaction_type'));
        }
    }

    /**
     * All types of splits must be equal.
     *
     * @param Validator $validator
     */
    public function validateTransactionTypesForUpdate(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        $types        = [];
        foreach ($transactions as $index => $transaction) {
            $types[] = $transaction['type'] ?? 'invalid';
        }
        $unique = array_unique($types);
        if (count($unique) > 1) {
            $validator->errors()->add('transactions.0.type', (string)trans('validation.transaction_types_equal'));

            return;
        }
    }

    /**
     * @param Validator $validator
     */
    private function validateEqualAccounts(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        // needs to be split
        if (count($transactions) < 2) {
            return;
        }
        $type    = $transactions[0]['type'] ?? 'withdrawal';
        $sources = [];
        $dests   = [];
        foreach ($transactions as $transaction) {
            $sources[] = sprintf('%d-%s', $transaction['source_id'] ?? 0, $transaction['source_name'] ?? '');
            $dests[]   = sprintf('%d-%s', $transaction['destination_id'] ?? 0, $transaction['destination_name'] ?? '');
        }
        $sources = array_unique($sources);
        $dests   = array_unique($dests);
        switch ($type) {
            case 'withdrawal':
                if (count($sources) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
            case 'deposit':
                if (count($dests) > 1) {
                    $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
            case'transfer':
                if (count($sources) > 1 || count($dests) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                    $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
        }
    }

    /**
     * @param Validator        $validator
     * @param TransactionGroup $transactionGroup
     */
    private function validateEqualAccountsForUpdate(Validator $validator, TransactionGroup $transactionGroup): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        // needs to be split
        if (count($transactions) < 2) {
            return;
        }
        $type    = $transactions[0]['type'] ?? strtolower($transactionGroup->transactionJournals()->first()->transactionType->type);
        $sources = [];
        $dests   = [];
        foreach ($transactions as $transaction) {
            $sources[] = sprintf('%d-%s', $transaction['source_id'] ?? 0, $transaction['source_name'] ?? '');
            $dests[]   = sprintf('%d-%s', $transaction['destination_id'] ?? 0, $transaction['destination_name'] ?? '');
        }
        $sources = array_unique($sources);
        $dests   = array_unique($dests);
        switch ($type) {
            case 'withdrawal':
                if (count($sources) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
            case 'deposit':
                if (count($dests) > 1) {
                    $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
            case'transfer':
                if (count($sources) > 1 || count($dests) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                    $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
        }
    }

    /**
     * @param Validator        $validator
     * @param TransactionGroup $transactionGroup
     */
    private function validateJournalIds(Validator $validator, TransactionGroup $transactionGroup): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        if (count($transactions) < 2) {
            return;
        }
        foreach ($transactions as $index => $transaction) {
            $journalId = $transaction['transaction_journal_id'] ?? null;
            $journalId = null === $journalId ? null : (int)$journalId;
            $count     = $transactionGroup->transactionJournals()->where('id', $journalId)->count();
            if (null === $journalId || (null !== $journalId && 0 !== $journalId && 0 === $count)) {
                $validator->errors()->add(sprintf('transactions.%d.source_name', $index), (string)trans('validation.need_id_in_edit'));
            }
        }
    }
}
