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

use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Validation\Validator;

/**
 * Trait TransactionValidation
 */
trait TransactionValidation
{
    /**
     * Validates the given account information. Switches on given transaction type.
     *
     * Inclusion of user and/or group is optional.
     */
    public function validateAccountInformation(Validator $validator, ?User $user = null, ?UserGroup $userGroup = null): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        app('log')->debug('Now in validateAccountInformation (TransactionValidation) ()');
        $transactions    = $this->getTransactionsArray($validator);
        $data            = $validator->getData();
        $transactionType = $data['type'] ?? 'invalid';

        app('log')->debug(sprintf('Going to loop %d transaction(s)', count($transactions)));

        /**
         * @var null|int $index
         * @var array    $transaction
         */
        foreach ($transactions as $index => $transaction) {
            $transaction['user']       = $user;
            $transaction['user_group'] = $userGroup;
            if (!is_int($index)) {
                continue;
            }
            $this->validateSingleAccount($validator, $index, $transactionType, $transaction);
        }
    }

    protected function getTransactionsArray(Validator $validator): array
    {
        app('log')->debug('Now in getTransactionsArray');
        $data         = $validator->getData();
        $transactions = [];
        if (array_key_exists('transactions', $data) && is_array($data['transactions'])) {
            app('log')->debug('Transactions key exists and is array.');
            $transactions = $data['transactions'];
        }
        if (array_key_exists('transactions', $data) && !is_array($data['transactions'])) {
            app('log')->debug(sprintf('Transactions key exists but is NOT array,  its a %s', gettype($data['transactions'])));
        }

        return $transactions;
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validateSingleAccount(Validator $validator, int $index, string $transactionType, array $transaction): void
    {
        app('log')->debug(sprintf('Now in validateSingleAccount(%d)', $index));

        /** @var AccountValidator $accountValidator */
        $accountValidator  = app(AccountValidator::class);

        if (array_key_exists('user', $transaction) && null !== $transaction['user']) {
            $accountValidator->setUser($transaction['user']);
        }
        if (array_key_exists('user_group', $transaction) && null !== $transaction['user_group']) {
            $accountValidator->setUserGroup($transaction['user_group']);
        }

        $transactionType   = $transaction['type'] ?? $transactionType;
        $accountValidator->setTransactionType($transactionType);

        // validate source account.
        $sourceId          = array_key_exists('source_id', $transaction) ? (int) $transaction['source_id'] : null;
        $sourceName        = array_key_exists('source_name', $transaction) ? (string) $transaction['source_name'] : null;
        $sourceIban        = array_key_exists('source_iban', $transaction) ? (string) $transaction['source_iban'] : null;
        $sourceNumber      = array_key_exists('source_number', $transaction) ? (string) $transaction['source_number'] : null;
        $source            = [
            'id'     => $sourceId,
            'name'   => $sourceName,
            'iban'   => $sourceIban,
            'number' => $sourceNumber,
        ];
        $validSource       = $accountValidator->validateSource($source);

        // do something with result:
        if (false === $validSource) {
            $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
            $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);

            return;
        }
        // validate destination account
        $destinationId     = array_key_exists('destination_id', $transaction) ? (int) $transaction['destination_id'] : null;
        $destinationName   = array_key_exists('destination_name', $transaction) ? (string) $transaction['destination_name'] : null;
        $destinationIban   = array_key_exists('destination_iban', $transaction) ? (string) $transaction['destination_iban'] : null;
        $destinationNumber = array_key_exists('destination_number', $transaction) ? (string) $transaction['destination_number'] : null;
        $destination       = [
            'id'     => $destinationId,
            'name'   => $destinationName,
            'iban'   => $destinationIban,
            'number' => $destinationNumber,
        ];
        $validDestination  = $accountValidator->validateDestination($destination);
        // do something with result:
        if (false === $validDestination) {
            $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
            $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);
        }
        // sanity check for reconciliation accounts. They can't both be null.
        $this->sanityCheckReconciliation($validator, $transactionType, $index, $source, $destination);

        // sanity check for currency information.
        $this->sanityCheckForeignCurrency($validator, $accountValidator, $transaction, $transactionType, $index);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function sanityCheckReconciliation(Validator $validator, string $transactionType, int $index, array $source, array $destination): void
    {
        app('log')->debug('Now in sanityCheckReconciliation');
        if (TransactionType::RECONCILIATION === ucfirst($transactionType)
            && null === $source['id'] && null === $source['name'] && null === $destination['id'] && null === $destination['name']
        ) {
            app('log')->debug('Both are NULL, error!');
            $validator->errors()->add(sprintf('transactions.%d.source_id', $index), trans('validation.reconciliation_either_account'));
            $validator->errors()->add(sprintf('transactions.%d.source_name', $index), trans('validation.reconciliation_either_account'));
            $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), trans('validation.reconciliation_either_account'));
            $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), trans('validation.reconciliation_either_account'));
        }

        if (TransactionType::RECONCILIATION === $transactionType
            && (null !== $source['id'] || null !== $source['name'])
            && (null !== $destination['id'] || null !== $destination['name'])) {
            app('log')->debug('Both are not NULL, error!');
            $validator->errors()->add(sprintf('transactions.%d.source_id', $index), trans('validation.reconciliation_either_account'));
            $validator->errors()->add(sprintf('transactions.%d.source_name', $index), trans('validation.reconciliation_either_account'));
            $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), trans('validation.reconciliation_either_account'));
            $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), trans('validation.reconciliation_either_account'));
        }
    }

    /**
     * TODO describe this method.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function sanityCheckForeignCurrency(
        Validator        $validator,
        AccountValidator $accountValidator,
        array            $transaction,
        string           $transactionType,
        int              $index
    ): void {
        app('log')->debug('Now in sanityCheckForeignCurrency()');
        if (0 !== $validator->errors()->count()) {
            app('log')->debug('Already have errors, return');

            return;
        }
        if (null === $accountValidator->source) {
            app('log')->debug('No source, return');

            return;
        }
        if (null === $accountValidator->destination) {
            app('log')->debug('No destination, return');

            return;
        }
        $source              = $accountValidator->source;
        $destination         = $accountValidator->destination;

        app('log')->debug(sprintf('Source: #%d "%s (%s)"', $source->id, $source->name, $source->accountType->type));
        app('log')->debug(sprintf('Destination: #%d "%s" (%s)', $destination->id, $destination->name, $source->accountType->type));

        if (!$this->isLiabilityOrAsset($source) || !$this->isLiabilityOrAsset($destination)) {
            app('log')->debug('Any account must be liability or asset account to continue.');

            return;
        }

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository   = app(AccountRepositoryInterface::class);
        $defaultCurrency     = app('amount')->getDefaultCurrency();
        $sourceCurrency      = $accountRepository->getAccountCurrency($source) ?? $defaultCurrency;
        $destinationCurrency = $accountRepository->getAccountCurrency($destination) ?? $defaultCurrency;
        // if both accounts have the same currency, continue.
        if ($sourceCurrency->code === $destinationCurrency->code) {
            app('log')->debug('Both accounts have the same currency, continue.');

            return;
        }
        app('log')->debug(sprintf('Source account expects %s', $sourceCurrency->code));
        app('log')->debug(sprintf('Destination account expects %s', $destinationCurrency->code));

        app('log')->debug(sprintf('Amount is %s', $transaction['amount']));

        if (TransactionTypeEnum::DEPOSIT->value === ucfirst($transactionType)) {
            app('log')->debug(sprintf('Processing as a "%s"', $transactionType));
            // use case: deposit from liability account to an asset account
            // the foreign amount must be in the currency of the source
            // the amount must be in the currency of the destination

            // no foreign currency information is present:
            if (!$this->hasForeignCurrencyInfo($transaction)) {
                $validator->errors()->add(sprintf('transactions.%d.foreign_amount', $index), (string) trans('validation.require_foreign_currency'));

                return;
            }

            // wrong currency information is present
            $foreignCurrencyCode = $transaction['foreign_currency_code'] ?? false;
            $foreignCurrencyId   = (int) ($transaction['foreign_currency_id'] ?? 0);
            app('log')->debug(sprintf('Foreign currency code seems to be #%d "%s"', $foreignCurrencyId, $foreignCurrencyCode), $transaction);
            if ($foreignCurrencyCode !== $sourceCurrency->code && $foreignCurrencyId !== $sourceCurrency->id) {
                $validator->errors()->add(sprintf('transactions.%d.foreign_currency_code', $index), (string) trans('validation.require_foreign_src'));

                return;
            }
        }
        if (TransactionTypeEnum::TRANSFER->value === ucfirst($transactionType) || TransactionTypeEnum::WITHDRAWAL->value === ucfirst($transactionType)) {
            app('log')->debug(sprintf('Processing as a "%s"', $transactionType));
            // use case: withdrawal from asset account to a liability account.
            // the foreign amount must be in the currency of the destination
            // the amount must be in the currency of the source

            // use case: transfer between accounts with different currencies.
            // the foreign amount must be in the currency of the destination
            // the amount must be in the currency of the source

            // no foreign currency information is present:
            if (!$this->hasForeignCurrencyInfo($transaction)) {
                $validator->errors()->add(sprintf('transactions.%d.foreign_amount', $index), (string) trans('validation.require_foreign_currency'));

                return;
            }

            // wrong currency information is present
            $foreignCurrencyCode = $transaction['foreign_currency_code'] ?? false;
            $foreignCurrencyId   = (int) ($transaction['foreign_currency_id'] ?? 0);
            app('log')->debug(sprintf('Foreign currency code seems to be #%d "%s"', $foreignCurrencyId, $foreignCurrencyCode), $transaction);
            if ($foreignCurrencyCode !== $destinationCurrency->code && $foreignCurrencyId !== $destinationCurrency->id) {
                app('log')->debug(sprintf('No match on code, "%s" vs "%s"', $foreignCurrencyCode, $destinationCurrency->code));
                app('log')->debug(sprintf('No match on ID, #%d vs #%d', $foreignCurrencyId, $destinationCurrency->id));
                $validator->errors()->add(sprintf('transactions.%d.foreign_amount', $index), (string) trans('validation.require_foreign_dest'));
            }
        }
    }

    private function isLiabilityOrAsset(Account $account): bool
    {
        return $this->isLiability($account) || $this->isAsset($account);
    }

    private function isLiability(Account $account): bool
    {
        $type = $account->accountType->type;
        if (in_array($type, config('firefly.valid_liabilities'), true)) {
            return true;
        }

        return false;
    }

    private function isAsset(Account $account): bool
    {
        $type = $account->accountType->type;

        return AccountType::ASSET === $type;
    }

    private function hasForeignCurrencyInfo(array $transaction): bool
    {
        if (!array_key_exists('foreign_currency_code', $transaction) && !array_key_exists('foreign_currency_id', $transaction)) {
            return false;
        }
        if (!array_key_exists('foreign_amount', $transaction)) {
            return false;
        }
        if ('' === $transaction['foreign_amount']) {
            return false;
        }
        if (0 === bccomp('0', (string) $transaction['foreign_amount'])) {
            return false;
        }

        return true;
    }

    /**
     * Validates the given account information. Switches on given transaction type.
     *
     * @throws FireflyException
     */
    public function validateAccountInformationUpdate(Validator $validator, TransactionGroup $transactionGroup): void
    {
        app('log')->debug('Now in validateAccountInformationUpdate()');
        if ($validator->errors()->count() > 0) {
            app('log')->debug('Validator already has errors, so return.');

            return;
        }

        $transactions = $this->getTransactionsArray($validator);

        /**
         * @var null|int $index
         * @var array    $transaction
         */
        foreach ($transactions as $index => $transaction) {
            if (!is_int($index)) {
                throw new FireflyException('Invalid data submitted: transaction is not array.');
            }
            $this->validateSingleUpdate($validator, $index, $transaction, $transactionGroup);
        }
    }

    protected function validateSingleUpdate(Validator $validator, int $index, array $transaction, TransactionGroup $transactionGroup): void
    {
        app('log')->debug('Now validating single account update in validateSingleUpdate()');

        // if no account types are given, just skip the check.
        if (
            !array_key_exists('source_id', $transaction)
            && !array_key_exists('source_name', $transaction)
            && !array_key_exists('destination_id', $transaction)
            && !array_key_exists('destination_name', $transaction)) {
            app('log')->debug('No account data has been submitted so will not validating account info.');

            return;
        }

        // create validator:
        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);

        // get the transaction type using the original transaction group:
        $accountValidator->setTransactionType($this->getTransactionType($transactionGroup, []));

        // validate if the submitted source ID/name/iban/number are valid
        if (
            array_key_exists('source_id', $transaction)
            || array_key_exists('source_name', $transaction)
            || array_key_exists('source_iban', $transaction)
            || array_key_exists('source_number', $transaction)
        ) {
            app('log')->debug('Will try to validate source account information.');
            $sourceId     = (int) ($transaction['source_id'] ?? 0);
            $sourceName   = $transaction['source_name'] ?? null;
            $sourceIban   = $transaction['source_iban'] ?? null;
            $sourceNumber = $transaction['source_number'] ?? null;
            $validSource  = $accountValidator->validateSource(
                ['id' => $sourceId, 'name' => $sourceName, 'iban' => $sourceIban, 'number' => $sourceNumber]
            );

            // do something with result:
            if (false === $validSource) {
                app('log')->warning('Looks like the source account is not valid so complain to the user about it.');
                $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_iban', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_number', $index), $accountValidator->sourceError);

                return;
            }
            app('log')->debug('Source account info is valid.');
        }

        if (
            array_key_exists('destination_id', $transaction)
            || array_key_exists('destination_name', $transaction)
            || array_key_exists('destination_iban', $transaction)
            || array_key_exists('destination_number', $transaction)
        ) {
            app('log')->debug('Will try to validate destination account information.');
            // at this point the validator may not have a source account, because it was never submitted for validation.
            // must add it ourselves or the validator can never check if the destination is correct.
            // the $transaction array must have a journal id or it's just one, this was validated before.
            if (null === $accountValidator->source) {
                app('log')->debug('Account validator has no source account, must find it.');
                $source = $this->getOriginalSource($transaction, $transactionGroup);
                if (null !== $source) {
                    app('log')->debug('Found a source!');
                    $accountValidator->source = $source;
                }
            }
            $destinationId     = (int) ($transaction['destination_id'] ?? 0);
            $destinationName   = $transaction['destination_name'] ?? null;
            $destinationIban   = $transaction['destination_iban'] ?? null;
            $destinationNumber = $transaction['destination_number'] ?? null;
            $array             = ['id' => $destinationId, 'name' => $destinationName, 'iban' => $destinationIban, 'number' => $destinationNumber];
            $validDestination  = $accountValidator->validateDestination($array);
            // do something with result:
            if (false === $validDestination) {
                app('log')->warning('Looks like the destination account is not valid so complain to the user about it.');
                $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
                $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);
            }
            app('log')->debug('Destination account info is valid.');
        }
        app('log')->debug('Done with validateSingleUpdate().');
    }

    private function getTransactionType(TransactionGroup $group, array $transactions): string
    {
        return $transactions[0]['type'] ?? strtolower((string) $group->transactionJournals()->first()?->transactionType->type);
    }

    private function getOriginalSource(array $transaction, TransactionGroup $transactionGroup): ?Account
    {
        if (1 === $transactionGroup->transactionJournals->count()) {
            $journal = $transactionGroup->transactionJournals->first();

            return $journal?->transactions()->where('amount', '<', 0)->first()?->account;
        }

        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            $journalId = (int) ($transaction['transaction_journal_id'] ?? 0);
            if ($journal->id === $journalId) {
                return $journal->transactions()->where('amount', '<', 0)->first()?->account;
            }
        }

        return null;
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     */
    public function validateOneRecurrenceTransaction(Validator $validator): void
    {
        app('log')->debug('Now in validateOneRecurrenceTransaction()');
        $transactions = $this->getTransactionsArray($validator);

        // need at least one transaction
        if (0 === count($transactions)) {
            $validator->errors()->add('transactions', (string) trans('validation.at_least_one_transaction'));
        }
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     */
    public function validateOneTransaction(Validator $validator): void
    {
        app('log')->debug('Now in validateOneTransaction');
        if ($validator->errors()->count() > 0) {
            app('log')->debug('Validator already has errors, so return.');

            return;
        }
        $transactions = $this->getTransactionsArray($validator);
        // need at least one transaction
        if (0 === count($transactions)) {
            $validator->errors()->add('transactions.0.description', (string) trans('validation.at_least_one_transaction'));
            app('log')->debug('Added error: at_least_one_transaction.');

            return;
        }
        app('log')->debug('Added NO errors.');
    }

    public function validateTransactionArray(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        $transactions = $this->getTransactionsArray($validator);
        foreach (array_keys($transactions) as $key) {
            if (!is_int($key)) {
                $validator->errors()->add('transactions.0.description', (string) trans('validation.at_least_one_transaction'));
                app('log')->debug('Added error: at_least_one_transaction.');

                return;
            }
        }
    }

    /**
     * All types of splits must be equal.
     */
    public function validateTransactionTypes(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        app('log')->debug('Now in validateTransactionTypes()');
        $transactions = $this->getTransactionsArray($validator);

        $types        = [];
        foreach ($transactions as $transaction) {
            $types[] = $transaction['type'] ?? 'invalid';
        }
        $unique       = array_unique($types);
        if (count($unique) > 1) {
            $validator->errors()->add('transactions.0.type', (string) trans('validation.transaction_types_equal'));

            return;
        }
        $first        = $unique[0] ?? 'invalid';
        if ('invalid' === $first) {
            $validator->errors()->add('transactions.0.type', (string) trans('validation.invalid_transaction_type'));
        }
    }

    /**
     * All types of splits must be equal.
     */
    public function validateTransactionTypesForUpdate(Validator $validator): void
    {
        app('log')->debug('Now in validateTransactionTypesForUpdate()');
        $transactions = $this->getTransactionsArray($validator);
        $types        = [];
        foreach ($transactions as $transaction) {
            $originalType = $this->getOriginalType((int) ($transaction['transaction_journal_id'] ?? 0));
            // if type is not set, fall back to the type of the journal, if one is given.
            $types[]      = $transaction['type'] ?? $originalType;
        }
        $unique       = array_unique($types);
        if (count($unique) > 1) {
            app('log')->warning('Add error for mismatch transaction types.');
            $validator->errors()->add('transactions.0.type', (string) trans('validation.transaction_types_equal'));

            return;
        }
        app('log')->debug('No errors in validateTransactionTypesForUpdate()');
    }

    private function getOriginalType(int $journalId): string
    {
        if (0 === $journalId) {
            return 'invalid';
        }

        /** @var null|TransactionJournal $journal */
        $journal = TransactionJournal::with(['transactionType'])->find($journalId);
        if (null !== $journal) {
            return strtolower($journal->transactionType->type);
        }

        return 'invalid';
    }

    private function validateEqualAccounts(Validator $validator): void
    {
        if ($validator->errors()->count() > 0) {
            return;
        }
        app('log')->debug('Now in validateEqualAccounts()');
        $transactions = $this->getTransactionsArray($validator);

        // needs to be split
        if (count($transactions) < 2) {
            return;
        }
        $type         = $transactions[0]['type'] ?? 'withdrawal';
        $sources      = [];
        $dests        = [];
        foreach ($transactions as $transaction) {
            $sources[] = sprintf('%d-%s', $transaction['source_id'] ?? 0, $transaction['source_name'] ?? '');
            $dests[]   = sprintf('%d-%s', $transaction['destination_id'] ?? 0, $transaction['destination_name'] ?? '');
        }
        $sources      = array_unique($sources);
        $dests        = array_unique($dests);

        switch ($type) {
            default:
            case 'withdrawal':
                if (count($sources) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string) trans('validation.all_accounts_equal'));
                }

                break;

            case 'deposit':
                if (count($dests) > 1) {
                    $validator->errors()->add('transactions.0.destination_id', (string) trans('validation.all_accounts_equal'));
                }

                break;

            case 'transfer':
                if (count($sources) > 1 || count($dests) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string) trans('validation.all_accounts_equal'));
                    $validator->errors()->add('transactions.0.destination_id', (string) trans('validation.all_accounts_equal'));
                }

                break;
        }
    }

    private function validateEqualAccountsForUpdate(Validator $validator, TransactionGroup $transactionGroup): void
    {
        if ($validator->errors()->count() > 0) {
            app('log')->debug('Validator already has errors, so return.');

            return;
        }

        app('log')->debug('Now in validateEqualAccountsForUpdate()');
        $transactions = $this->getTransactionsArray($validator);

        if (2 !== count($transactions)) {
            app('log')->debug('Less than 2 transactions, do nothing.');

            return;
        }
        $type         = $this->getTransactionType($transactionGroup, $transactions);

        // compare source IDs, destination IDs, source names and destination names.
        // I think I can get away with one combination being equal, as long as the rest
        // of the code picks up on this as well.
        // either way all fields must be blank or all equal
        // but if IDs are equal don't bother with the names.
        $comparison   = $this->collectComparisonData($transactions);
        $result       = $this->compareAccountData($type, $comparison);
        if (false === $result) {
            if ('withdrawal' === $type) {
                $validator->errors()->add('transactions.0.source_id', (string) trans('validation.all_accounts_equal'));
            }
            if ('deposit' === $type) {
                $validator->errors()->add('transactions.0.destination_id', (string) trans('validation.all_accounts_equal'));
            }
            if ('transfer' === $type) {
                $validator->errors()->add('transactions.0.source_id', (string) trans('validation.all_accounts_equal'));
                $validator->errors()->add('transactions.0.destination_id', (string) trans('validation.all_accounts_equal'));
            }
            app('log')->warning('Add error about equal accounts.');

            return;
        }
        app('log')->debug('No errors found in validateEqualAccountsForUpdate');
    }

    private function collectComparisonData(array $transactions): array
    {
        $fields     = ['source_id', 'destination_id', 'source_name', 'destination_name'];
        $comparison = [];
        foreach ($fields as $field) {
            $comparison[$field] = [];

            /** @var array $transaction */
            foreach ($transactions as $transaction) {
                // source or destination may be omitted. If this is the case, use the original source / destination name + ID.
                $originalData         = $this->getOriginalData((int) ($transaction['transaction_journal_id'] ?? 0));

                // get field.
                $comparison[$field][] = $transaction[$field] ?? $originalData[$field];
            }
        }

        return $comparison;
    }

    private function getOriginalData(int $journalId): array
    {
        $return      = [
            'source_id'        => 0,
            'source_name'      => '',
            'destination_id'   => 0,
            'destination_name' => '',
        ];
        if (0 === $journalId) {
            return $return;
        }

        /** @var null|Transaction $source */
        $source      = Transaction::where('transaction_journal_id', $journalId)->where('amount', '<', 0)->with(['account'])->first();
        if (null !== $source) {
            $return['source_id']   = $source->account_id;
            $return['source_name'] = $source->account->name;
        }

        /** @var null|Transaction $destination */
        $destination = Transaction::where('transaction_journal_id', $journalId)->where('amount', '>', 0)->with(['account'])->first();
        if (null !== $destination) {
            $return['destination_id']   = $destination->account_id;
            $return['destination_name'] = $destination->account->name;
        }

        return $return;
    }

    private function compareAccountData(string $type, array $comparison): bool
    {
        return match ($type) {
            default    => $this->compareAccountDataWithdrawal($comparison),
            'deposit'  => $this->compareAccountDataDeposit($comparison),
            'transfer' => $this->compareAccountDataTransfer($comparison),
        };
    }

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

    private function arrayEqual(array $array): bool
    {
        return 1 === count(array_unique($array));
    }

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
