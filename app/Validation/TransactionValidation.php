<?php
/**
 * TransactionValidation.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Validation\Validator;
use Log;

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
        //Log::debug('Now in validateAccountInformation()');
        $data = $validator->getData();

        $transactionType = $data['type'] ?? 'invalid';
        $transactions    = $data['transactions'] ?? [];

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);

        Log::debug(sprintf('Going to loop %d transaction(s)', count($transactions)));
        foreach ($transactions as $index => $transaction) {
            $transactionType = $transaction['type'] ?? $transactionType;
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
     * Validates the given account information. Switches on given transaction type.
     *
     * @param Validator $validator
     */
    public function validateAccountInformationUpdate(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);

        foreach ($transactions as $index => $transaction) {
            $originalType    = $this->getOriginalType((int)($transaction['transaction_journal_id'] ?? 0));
            $originalData    = $this->getOriginalData((int)($transaction['transaction_journal_id'] ?? 0));
            $transactionType = $transaction['type'] ?? $originalType;
            $accountValidator->setTransactionType($transactionType);

            // if no account types are given, just skip the check.
            if (!isset($transaction['source_id'])
                && !isset($transaction['source_name'])
                && !isset($transaction['destination_id'])
                && !isset($transaction['destination_name'])) {
                continue;
            }

            // validate source account.
            $sourceId    = isset($transaction['source_id']) ? (int)$transaction['source_id'] : $originalData['source_id'];
            $sourceName  = $transaction['source_name'] ?? $originalData['source_name'];
            $validSource = $accountValidator->validateSource($sourceId, $sourceName);

            // do something with result:
            if (false === $validSource) {
                $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);

                continue;
            }
            // validate destination account
            $destinationId    = isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : $originalData['destination_id'];
            $destinationName  = $transaction['destination_name'] ?? $originalData['destination_name'];
            $validDestination = $accountValidator->validateDestination($destinationId, $destinationName);
            // do something with result:
            if (false === $validDestination) {
                $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
                $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);

                continue;
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
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }
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

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        foreach ($transactions as $index => $transaction) {
            // if foreign amount is present, then the currency must be as well.
            if (isset($transaction['foreign_amount']) && !(isset($transaction['foreign_currency_id']) || isset($transaction['foreign_currency_code']))
                && 0 !== bccomp('0', $transaction['foreign_amount'])
            ) {
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

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        $groupTitle = $data['group_title'] ?? '';
        if ('' === $groupTitle && count($transactions) > 1) {
            $validator->errors()->add('group_title', (string)trans('validation.group_title_mandatory'));
        }
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     *
     * @param Validator $validator
     */
    public function validateOneRecurrenceTransaction(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        // need at least one transaction
        if (0 === count($transactions)) {
            $validator->errors()->add('transactions', (string)trans('validation.at_least_one_transaction'));
        }
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     *
     * @param Validator $validator
     */
    public function validateOneRecurrenceTransactionUpdate(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? null;

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        if (null === $transactions) {
            return;
        }
        // need at least one transaction
        if (0 === count($transactions)) {
            $validator->errors()->add('transactions', (string)trans('validation.at_least_one_transaction'));
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
        if (!is_countable($transactions)) {
            $validator->errors()->add('transactions.0.description', (string)trans('validation.at_least_one_transaction'));

            return;
        }
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

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        $types = [];
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

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        $types = [];
        foreach ($transactions as $index => $transaction) {
            $originalType = $this->getOriginalType((int)($transaction['transaction_journal_id'] ?? 0));
            // if type is not set, fall back to the type of the journal, if one is given.


            $types[] = $transaction['type'] ?? $originalType;
        }
        $unique = array_unique($types);
        if (count($unique) > 1) {
            $validator->errors()->add('transactions.0.type', (string)trans('validation.transaction_types_equal'));

            return;
        }
    }

    /**
     * @param array $array
     *
     * @return bool
     */
    private function arrayEqual(array $array): bool
    {
        return 1 === count(array_unique($array));
    }

    /**
     * @param int $journalId
     *
     * @return array
     */
    private function getOriginalData(int $journalId): array
    {
        $return = [
            'source_id'        => 0,
            'source_name'      => '',
            'destination_id'   => 0,
            'destination_name' => '',
        ];
        if (0 === $journalId) {
            return $return;
        }
        /** @var Transaction $source */
        $source = Transaction::where('transaction_journal_id', $journalId)->where('amount', '<', 0)->with(['account'])->first();
        if (null !== $source) {
            $return['source_id']   = $source->account_id;
            $return['source_name'] = $source->account->name;
        }
        /** @var Transaction $destination */
        $destination = Transaction::where('transaction_journal_id', $journalId)->where('amount', '>', 0)->with(['account'])->first();
        if (null !== $source) {
            $return['destination_id']   = $destination->account_id;
            $return['destination_name'] = $destination->account->name;
        }

        return $return;
    }

    /**
     * @param int $journalId
     *
     * @return string
     */
    private function getOriginalType(int $journalId): string
    {
        if (0 === $journalId) {
            return 'invalid';
        }
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::with(['transactionType'])->find($journalId);
        if (null !== $journal) {
            return strtolower($journal->transactionType->type);
        }

        return 'invalid';
    }

    /**
     * @param Validator $validator
     */
    private function validateEqualAccounts(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

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

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

        // needs to be split
        if (count($transactions) < 2) {
            return;
        }
        $type = $transactions[0]['type'] ?? strtolower($transactionGroup->transactionJournals()->first()->transactionType->type);

        // compare source ID's, destination ID's, source names and destination names.
        // I think I can get away with one combination being equal, as long as the rest
        // of the code picks up on this as well.
        // either way all fields must be blank or all equal
        // but if ID's are equal don't bother with the names.

        $fields     = ['source_id', 'destination_id', 'source_name', 'destination_name'];
        $comparison = [];
        foreach ($fields as $field) {
            $comparison[$field] = [];
            /** @var array $transaction */
            foreach ($transactions as $transaction) {
                // source or destination may be omitted. If this is the case, use the original source / destination name + ID.
                $originalData = $this->getOriginalData((int)($transaction['transaction_journal_id'] ?? 0));

                // get field.
                $comparison[$field][] = $transaction[$field] ?? $originalData[$field];
            }
        }
        // TODO not the best way to loop this.
        switch ($type) {
            case 'withdrawal':
                if ($this->arrayEqual($comparison['source_id'])) {
                    // source ID's are equal, return void.
                    return;
                }
                if ($this->arrayEqual($comparison['source_name'])) {
                    // source names are equal, return void.
                    return;
                }
                // add error:
                $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                break;
            case 'deposit':
                if ($this->arrayEqual($comparison['destination_id'])) {
                    // destination ID's are equal, return void.
                    return;
                }
                if ($this->arrayEqual($comparison['destination_name'])) {
                    // destination names are equal, return void.
                    return;
                }
                // add error:
                $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                break;
            case 'transfer':
                if ($this->arrayEqual($comparison['source_id'])) {
                    // source ID's are equal, return void.
                    return;
                }
                if ($this->arrayEqual($comparison['source_name'])) {
                    // source names are equal, return void.
                    return;
                }
                if ($this->arrayEqual($comparison['destination_id'])) {
                    // destination ID's are equal, return void.
                    return;
                }
                if ($this->arrayEqual($comparison['destination_name'])) {
                    // destination names are equal, return void.
                    return;
                }
                // add error:
                $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
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

        if (!is_countable($data['transactions'])) {
            $validator->errors()->add(
                'transactions.0.description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')])
            );

            return;
        }

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
