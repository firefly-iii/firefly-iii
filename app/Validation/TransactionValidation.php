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

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
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
        $data            = $validator->getData();
        $transactions    = $data['transactions'] ?? [];
        $idField         = 'description';
        $transactionType = $data['type'] ?? 'invalid';
        // get transaction type:
        if (!isset($data['type'])) {
            // the journal may exist in the request:
            /** @var Transaction $transaction */
            $transaction = $this->route()->parameter('transaction');
            if (null !== $transaction) {
                $transactionType = strtolower($transaction->transactionJournal->transactionType->type);
            }
        }

        foreach ($transactions as $index => $transaction) {
            $sourceId           = isset($transaction['source_id']) ? (int)$transaction['source_id'] : null;
            $sourceName         = $transaction['source_name'] ?? null;
            $destinationId      = isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : null;
            $destinationName    = $transaction['destination_name'] ?? null;
            $sourceAccount      = null;
            $destinationAccount = null;
            switch ($transactionType) {
                case 'withdrawal':
                    $idField            = 'transactions.' . $index . '.source_id';
                    $nameField          = 'transactions.' . $index . '.source_name';
                    $sourceAccount      = $this->assetAccountExists($validator, $sourceId, $sourceName, $idField, $nameField);
                    $idField            = 'transactions.' . $index . '.destination_id';
                    $destinationAccount = $this->opposingAccountExists($validator, AccountType::EXPENSE, $destinationId, $destinationName, $idField);
                    break;
                case 'deposit':
                    $idField       = 'transactions.' . $index . '.source_id';
                    $sourceAccount = $this->opposingAccountExists($validator, AccountType::REVENUE, $sourceId, $sourceName, $idField);

                    $idField            = 'transactions.' . $index . '.destination_id';
                    $nameField          = 'transactions.' . $index . '.destination_name';
                    $destinationAccount = $this->assetAccountExists($validator, $destinationId, $destinationName, $idField, $nameField);
                    break;
                case 'transfer':
                    $idField       = 'transactions.' . $index . '.source_id';
                    $nameField     = 'transactions.' . $index . '.source_name';
                    $sourceAccount = $this->assetAccountExists($validator, $sourceId, $sourceName, $idField, $nameField);

                    $idField            = 'transactions.' . $index . '.destination_id';
                    $nameField          = 'transactions.' . $index . '.destination_name';
                    $destinationAccount = $this->assetAccountExists($validator, $destinationId, $destinationName, $idField, $nameField);
                    break;
                default:
                    $validator->errors()->add($idField, (string)trans('validation.invalid_account_info'));

                    return;

            }
            // add some errors in case of same account submitted:
            if (null !== $sourceAccount && null !== $destinationAccount && $sourceAccount->id === $destinationAccount->id) {
                $validator->errors()->add($idField, (string)trans('validation.source_equals_destination'));
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
        $data               = $validator->getData();
        $transactions       = $data['transactions'] ?? [];
        $journalDescription = (string)($data['description'] ?? null);
        $validDescriptions  = 0;
        foreach ($transactions as $index => $transaction) {
            if ('' !== (string)($transaction['description'] ?? null)) {
                $validDescriptions++;
            }
        }

        // no valid descriptions and empty journal description? error.
        if (0 === $validDescriptions && '' === $journalDescription) {
            $validator->errors()->add('description', (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.description')]));
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
            // must have currency info.
            if (isset($transaction['foreign_amount'])
                && !(isset($transaction['foreign_currency_id'])
                     || isset($transaction['foreign_currency_code']))) {
                $validator->errors()->add(
                    'transactions.' . $index . '.foreign_amount',
                    (string)trans('validation.require_currency_info')
                );
            }
        }
    }

    /**
     * Adds an error to the validator when any transaction descriptions are equal to the journal description.
     *
     * @param Validator $validator
     */
    public function validateJournalDescription(Validator $validator): void
    {
        $data               = $validator->getData();
        $transactions       = $data['transactions'] ?? [];
        $journalDescription = (string)($data['description'] ?? null);
        foreach ($transactions as $index => $transaction) {
            $description = (string)($transaction['description'] ?? null);
            // description cannot be equal to journal description.
            if ($description === $journalDescription) {
                $validator->errors()->add('transactions.' . $index . '.description', (string)trans('validation.equal_description'));
            }
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
        if (0 === \count($transactions)) {
            $validator->errors()->add('description', (string)trans('validation.at_least_one_transaction'));
        }
    }

    /**
     * Make sure that all the splits accounts are valid in combination with each other.
     *
     * @param Validator $validator
     */
    public function validateSplitAccounts(Validator $validator): void
    {
        $data  = $validator->getData();
        $count = isset($data['transactions']) ? \count($data['transactions']) : 0;
        if ($count < 2) {
            return;
        }
        // this is pretty much impossible:
        // @codeCoverageIgnoreStart
        if (!isset($data['type'])) {
            // the journal may exist in the request:
            /** @var Transaction $transaction */
            $transaction = $this->route()->parameter('transaction');
            if (null === $transaction) {
                return;
            }
            $data['type'] = strtolower($transaction->transactionJournal->transactionType->type);
        }
        // @codeCoverageIgnoreEnd

        // collect all source ID's and destination ID's, if present:
        $sources      = [];
        $destinations = [];

        foreach ($data['transactions'] as $transaction) {
            $sources[]      = isset($transaction['source_id']) ? (int)$transaction['source_id'] : 0;
            $destinations[] = isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : 0;
        }
        $destinations = array_unique($destinations);
        $sources      = array_unique($sources);
        // switch on type:
        switch ($data['type']) {
            case 'withdrawal':
                if (\count($sources) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
            case 'deposit':
                if (\count($destinations) > 1) {
                    $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
            case 'transfer':
                if (\count($sources) > 1 || \count($destinations) > 1) {
                    $validator->errors()->add('transactions.0.source_id', (string)trans('validation.all_accounts_equal'));
                    $validator->errors()->add('transactions.0.destination_id', (string)trans('validation.all_accounts_equal'));
                }
                break;
        }
    }

    /**
     * Adds an error to the validator when the user submits a split transaction (more than 1 transactions)
     * but does not give them a description.
     *
     * @param Validator $validator
     */
    public function validateSplitDescriptions(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        foreach ($transactions as $index => $transaction) {
            $description = (string)($transaction['description'] ?? null);
            // filled description is mandatory for split transactions.
            if ('' === $description && \count($transactions) > 1) {
                $validator->errors()->add(
                    'transactions.' . $index . '.description',
                    (string)trans('validation.filled', ['attribute' => (string)trans('validation.attributes.transaction_description')])
                );
            }
        }
    }

    /**
     * Throws an error when this asset account is invalid.
     *
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * @param Validator   $validator
     * @param int|null    $accountId
     * @param null|string $accountName
     * @param string      $idField
     * @param string      $nameField
     *
     * @return null|Account
     */
    protected function assetAccountExists(Validator $validator, ?int $accountId, ?string $accountName, string $idField, string $nameField): ?Account
    {
        /** @var User $admin */
        $admin       = auth()->user();
        $accountId   = (int)$accountId;
        $accountName = (string)$accountName;
        // both empty? hard exit.
        if ($accountId < 1 && '' === $accountName) {
            $validator->errors()->add($idField, (string)trans('validation.filled', ['attribute' => $idField]));

            return null;
        }
        // ID belongs to user and is asset account:
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser($admin);
        $set = $repository->getAccountsById([$accountId]);
        Log::debug(sprintf('Count of accounts found by ID %d is: %d', $accountId, $set->count()));
        if (1 === $set->count()) {
            /** @var Account $first */
            $first = $set->first();
            if ($first->accountType->type !== AccountType::ASSET) {
                $validator->errors()->add($idField, (string)trans('validation.belongs_user'));

                return null;
            }

            // we ignore the account name at this point.
            return $first;
        }

        $account = $repository->findByName($accountName, [AccountType::ASSET]);
        if (null === $account) {
            $validator->errors()->add($nameField, (string)trans('validation.belongs_user'));

            return null;
        }

        return $account;
    }

    /**
     * Throws an error when the given opposing account (of type $type) is invalid.
     * Empty data is allowed, system will default to cash.
     *
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * @param Validator   $validator
     * @param string      $type
     * @param int|null    $accountId
     * @param null|string $accountName
     * @param string      $idField
     *
     * @return null|Account
     */
    protected function opposingAccountExists(Validator $validator, string $type, ?int $accountId, ?string $accountName, string $idField): ?Account
    {
        /** @var User $admin */
        $admin       = auth()->user();
        $accountId   = (int)$accountId;
        $accountName = (string)$accountName;
        // both empty? done!
        if ($accountId < 1 && '' === $accountName) {
            return null;
        }
        if (0 !== $accountId) {
            // ID belongs to user and is $type account:
            /** @var AccountRepositoryInterface $repository */
            $repository = app(AccountRepositoryInterface::class);
            $repository->setUser($admin);
            $set = $repository->getAccountsById([$accountId]);
            if (1 === $set->count()) {
                /** @var Account $first */
                $first = $set->first();
                if ($first->accountType->type !== $type) {
                    $validator->errors()->add($idField, (string)trans('validation.belongs_user'));

                    return null;
                }

                // we ignore the account name at this point.
                return $first;
            }
        }

        // not having an opposing account by this name is NOT a problem.
        return null;
    }
}
