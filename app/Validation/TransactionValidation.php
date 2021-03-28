<?php
/**
 * TransactionValidation.php
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

namespace FireflyIII\Validation;

use FireflyIII\Models\Account;
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
        Log::debug('Now in validateAccountInformation()');
        $transactions = $this->getTransactionsArray($validator);
        $data         = $validator->getData();

        $transactionType = $data['type'] ?? 'invalid';

        Log::debug(sprintf('Going to loop %d transaction(s)', count($transactions)));
        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($transactions as $index => $transaction) {
            if (!is_int($index)) {
                continue;
            }
            $this->validateSingleAccount($validator, $index, $transactionType, $transaction);
        }
    }

    /**
     * @param Validator $validator
     *
     * @return array
     */
    protected function getTransactionsArray(Validator $validator): array
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        if (!is_countable($transactions)) {
            Log::error(sprintf('Transactions array is not countable, because its a %s', gettype($transactions)));

            return [];
        }
        // a superfluous check but you never know.
        if (!is_array($transactions)) {
            Log::error(sprintf('Transactions array is not an array, because its a %s', gettype($transactions)));

            return [];
        }

        return $transactions;
    }

    /**
     * @param Validator $validator
     * @param int       $index
     * @param string    $transactionType
     * @param array     $transaction
     */
    protected function validateSingleAccount(Validator $validator, int $index, string $transactionType, array $transaction): void
    {
        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);

        $transactionType = $transaction['type'] ?? $transactionType;
        $accountValidator->setTransactionType($transactionType);

        // validate source account.
        $sourceId    = isset($transaction['source_id']) ? (int)$transaction['source_id'] : null;
        $sourceName  = isset($transaction['source_name']) ? (string)$transaction['source_name'] : null;
        $sourceIban  = isset($transaction['source_iban']) ? (string)$transaction['source_iban'] : null;
        $validSource = $accountValidator->validateSource($sourceId, $sourceName, $sourceIban);

        // do something with result:
        if (false === $validSource) {
            $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
            $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);

            return;
        }
        // validate destination account
        $destinationId    = isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : null;
        $destinationName  = isset($transaction['destination_name']) ? (string)$transaction['destination_name'] : null;
        $destinationIban  = isset($transaction['destination_iban']) ? (string)$transaction['destination_iban'] : null;
        $validDestination = $accountValidator->validateDestination($destinationId, $destinationName, $destinationIban);
        // do something with result:
        if (false === $validDestination) {
            $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
            $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);
        }
    }

    /**
     * Validates the given account information. Switches on given transaction type.
     *
     * @param Validator        $validator
     * @param TransactionGroup $transactionGroup
     */
    public function validateAccountInformationUpdate(Validator $validator, TransactionGroup $transactionGroup): void
    {
        Log::debug('Now in validateAccountInformationUpdate()');
        $transactions = $this->getTransactionsArray($validator);

        /**
         * @var int   $index
         * @var array $transaction
         */
        foreach ($transactions as $index => $transaction) {
            $this->validateSingleUpdate($validator, $index, $transaction, $transactionGroup);
        }
    }

    /**
     * @param Validator        $validator
     * @param int              $index
     * @param array            $transaction
     * @param TransactionGroup $transactionGroup
     */
    protected function validateSingleUpdate(Validator $validator, int $index, array $transaction, TransactionGroup $transactionGroup): void
    {
        Log::debug('Now validating single account update in validateSingleUpdate()');

        // if no account types are given, just skip the check.
        if (!isset($transaction['source_id'])
            && !isset($transaction['source_name'])
            && !isset($transaction['destination_id'])
            && !isset($transaction['destination_name'])) {
            Log::debug('No account data has been submitted so will not validating account info.');

            return;
        }
        // create validator:
        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);

        // get the transaction type using the original transaction group:
        $accountValidator->setTransactionType($this->getTransactionType($transactionGroup, []));

        // validate if the submitted source and / or name are valid
        if (array_key_exists('source_id', $transaction) || array_key_exists('source_name', $transaction)) {
            Log::debug('Will try to validate source account information.');
            $sourceId    = (int)($transaction['source_id'] ?? 0);
            $sourceName  = $transaction['source_name'] ?? null;
            $validSource = $accountValidator->validateSource($sourceId, $sourceName, null);

            // do something with result:
            if (false === $validSource) {
                Log::warning('Looks like the source account is not valid so complain to the user about it.');
                $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);

                return;
            }
            Log::debug('Source account info is valid.');
        }

        if (array_key_exists('destination_id', $transaction) || array_key_exists('destination_name', $transaction)) {
            Log::debug('Will try to validate destination account information.');
            // at this point the validator may not have a source account, because it was never submitted for validation.
            // must add it ourselves or the validator can never check if the destination is correct.
            // the $transaction array must have a journal id or it's just one, this was validated before.
            if (null === $accountValidator->source) {
                Log::debug('Account validator has no source account, must find it.');
                $source = $this->getOriginalSource($transaction, $transactionGroup);
                if (null !== $source) {
                    Log::debug('Found a source!');
                    $accountValidator->source = $source;
                }
            }
            $destinationId    = (int)($transaction['destination_id'] ?? 0);
            $destinationName  = $transaction['destination_name'] ?? null;
            $validDestination = $accountValidator->validateDestination($destinationId, $destinationName, null);
            // do something with result:
            if (false === $validDestination) {
                Log::warning('Looks like the destination account is not valid so complain to the user about it.');
                $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
                $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);
            }
            Log::debug('Destination account info is valid.');
        }
        Log::debug('Done with validateSingleUpdate().');
    }

    /**
     * @param TransactionGroup $group
     * @param array            $transactions
     *
     * @return string
     */
    private function getTransactionType(TransactionGroup $group, array $transactions): string
    {
        return $transactions[0]['type'] ?? strtolower($group->transactionJournals()->first()->transactionType->type);
    }

    /**
     * @param array            $transaction
     * @param TransactionGroup $transactionGroup
     *
     * @return Account|null
     */
    private function getOriginalSource(array $transaction, TransactionGroup $transactionGroup): ?Account
    {
        if (1 === $transactionGroup->transactionJournals->count()) {
            $journal = $transactionGroup->transactionJournals->first();

            return $journal->transactions()->where('amount', '<', 0)->first()->account;
        }
        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            if ((int)$journal->id === (int)$transaction['transaction_journal_id']) {
                return $journal->transactions()->where('amount', '<', 0)->first()->account;
            }
        }

        return null;
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     *
     * @param Validator $validator
     */
    public function validateOneRecurrenceTransaction(Validator $validator): void
    {
        Log::debug('Now in validateOneRecurrenceTransaction()');
        $transactions = $this->getTransactionsArray($validator);

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
        Log::debug('Now in validateOneTransaction()');
        $transactions = $this->getTransactionsArray($validator);
        // need at least one transaction
        if (0 === count($transactions)) {
            $validator->errors()->add('transactions.0.description', (string)trans('validation.at_least_one_transaction'));
            Log::debug('Added error: at_least_one_transaction.');

            return;
        }
        Log::debug('Added NO errors.');
    }

    /**
     * @param Validator $validator
     */
    public function validateTransactionArray(Validator $validator): void
    {
        $transactions = $this->getTransactionsArray($validator);
        foreach ($transactions as $key => $value) {
            if (!is_int($key)) {
                $validator->errors()->add('transactions.0.description', (string)trans('validation.at_least_one_transaction'));
                Log::debug('Added error: at_least_one_transaction.');

                return;
            }
        }
    }

    /**
     * All types of splits must be equal.
     *
     * @param Validator $validator
     */
    public function validateTransactionTypes(Validator $validator): void
    {
        Log::debug('Now in validateTransactionTypes()');
        $transactions = $this->getTransactionsArray($validator);

        $types = [];
        foreach ($transactions as $transaction) {
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
     * @param Validator        $validator
     * @param TransactionGroup $transactionGroup
     */
    public function validateTransactionTypesForUpdate(Validator $validator, TransactionGroup $transactionGroup): void
    {
        Log::debug('Now in validateTransactionTypesForUpdate()');
        $transactions = $this->getTransactionsArray($validator);
        $types        = [];
        foreach ($transactions as $transaction) {
            $originalType = $this->getOriginalType((int)($transaction['transaction_journal_id'] ?? 0));
            // if type is not set, fall back to the type of the journal, if one is given.
            $types[] = $transaction['type'] ?? $originalType;
        }
        $unique = array_unique($types);
        if (count($unique) > 1) {
            Log::warning('Add error for mismatch transaction types.');
            $validator->errors()->add('transactions.0.type', (string)trans('validation.transaction_types_equal'));

            return;
        }
        Log::debug('No errors in validateTransactionTypesForUpdate()');
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
        Log::debug('Now in validateEqualAccounts()');
        $transactions = $this->getTransactionsArray($validator);

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
            default:
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
        Log::debug('Now in validateEqualAccountsForUpdate()');
        $transactions = $this->getTransactionsArray($validator);

        if (2 !== count($transactions)) {
            Log::debug('Less than 2 transactions, do nothing.');

            return;
        }
        $type = $this->getTransactionType($transactionGroup, $transactions);

        // compare source ID's, destination ID's, source names and destination names.
        // I think I can get away with one combination being equal, as long as the rest
        // of the code picks up on this as well.
        // either way all fields must be blank or all equal
        // but if ID's are equal don't bother with the names.
        $comparison = $this->collectComparisonData($transactions);
        $result     = $this->compareAccountData($type, $comparison);
        if (false === $result) {
            if ('withdrawal' === $type) {
                $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
            }
            if ('deposit' === $type) {
                $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
            }
            if ('transfer' === $type) {
                $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
            }
            Log::warning('Add error about equal accounts.');

            return;
        }
        Log::debug('No errors found in validateEqualAccountsForUpdate');
    }

    /**
     * @param array $transactions
     *
     * @return array
     */
    private function collectComparisonData(array $transactions): array
    {
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

        return $comparison;
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
     * @param string $type
     * @param array  $comparison
     *
     * @return bool
     */
    private function compareAccountData(string $type, array $comparison): bool
    {
        switch ($type) {
            default:
            case 'withdrawal':
                return $this->compareAccountDataWithdrawal($comparison);
            case 'deposit':
                return $this->compareAccountDataDeposit($comparison);
            case 'transfer':
                return $this->compareAccountDataTransfer($comparison);
        }
    }

    /**
     * @param array $comparison
     *
     * @return bool
     */
    private function compareAccountDataWithdrawal(array $comparison): bool
    {
        if ($this->arrayEqual($comparison['source_id'])) {
            // source ID's are equal, return void.
            return true;
        }
        if ($this->arrayEqual($comparison['source_name'])) {
            // source names are equal, return void.
            return true;
        }

        return false;
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
     * @param array $comparison
     *
     * @return bool
     */
    private function compareAccountDataDeposit(array $comparison): bool
    {
        if ($this->arrayEqual($comparison['destination_id'])) {
            // destination ID's are equal, return void.
            return true;
        }
        if ($this->arrayEqual($comparison['destination_name'])) {
            // destination names are equal, return void.
            return true;
        }

        return false;
    }

    /**
     * @param array $comparison
     *
     * @return bool
     */
    private function compareAccountDataTransfer(array $comparison): bool
    {
        if ($this->arrayEqual($comparison['source_id'])) {
            // source ID's are equal, return void.
            return true;
        }
        if ($this->arrayEqual($comparison['source_name'])) {
            // source names are equal, return void.
            return true;
        }
        if ($this->arrayEqual($comparison['destination_id'])) {
            // destination ID's are equal, return void.
            return true;
        }
        if ($this->arrayEqual($comparison['destination_name'])) {
            // destination names are equal, return void.
            return true;
        }

        return false;
    }
}
