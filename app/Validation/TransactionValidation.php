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

use FireflyIII\Models\Transaction;
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
     *
     * @throws \FireflyIII\Exceptions\FireflyException
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
     * @param Validator $validator
     */
    public function validateGroupDescription(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        $groupTitle   = $data['group_title'] ?? '';
        if ('' === $groupTitle && \count($transactions) > 1) {
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

    //    /**
    //     * Throws an error when this asset account is invalid.
    //     *
    //     * @noinspection MoreThanThreeArgumentsInspection
    //     *
    //     * @param Validator   $validator
    //     * @param int|null    $accountId
    //     * @param null|string $accountName
    //     * @param string      $idField
    //     * @param string      $nameField
    //     *
    //     * @return null|Account
    //     */
    //    protected function assetAccountExists(Validator $validator, ?int $accountId, ?string $accountName, string $idField, string $nameField): ?Account
    //    {
    //        /** @var User $admin */
    //        $admin       = auth()->user();
    //        $accountId   = (int)$accountId;
    //        $accountName = (string)$accountName;
    //        // both empty? hard exit.
    //        if ($accountId < 1 && '' === $accountName) {
    //            $validator->errors()->add($idField, (string)trans('validation.filled', ['attribute' => $idField]));
    //
    //            return null;
    //        }
    //        // ID belongs to user and is asset account:
    //        /** @var AccountRepositoryInterface $repository */
    //        $repository = app(AccountRepositoryInterface::class);
    //        $repository->setUser($admin);
    //        $set = $repository->getAccountsById([$accountId]);
    //        Log::debug(sprintf('Count of accounts found by ID %d is: %d', $accountId, $set->count()));
    //        if (1 === $set->count()) {
    //            /** @var Account $first */
    //            $first = $set->first();
    //            if ($first->accountType->type !== AccountType::ASSET) {
    //                $validator->errors()->add($idField, (string)trans('validation.belongs_user'));
    //
    //                return null;
    //            }
    //
    //            // we ignore the account name at this point.
    //            return $first;
    //        }
    //
    //        $account = $repository->findByName($accountName, [AccountType::ASSET]);
    //        if (null === $account) {
    //            $validator->errors()->add($nameField, (string)trans('validation.belongs_user'));
    //
    //            return null;
    //        }
    //
    //        return $account;
    //    }
    //
    //    /**
    //     * Throws an error when the given opposing account (of type $type) is invalid.
    //     * Empty data is allowed, system will default to cash.
    //     *
    //     * @noinspection MoreThanThreeArgumentsInspection
    //     *
    //     * @param Validator   $validator
    //     * @param string      $type
    //     * @param int|null    $accountId
    //     * @param null|string $accountName
    //     * @param string      $idField
    //     *
    //     * @return null|Account
    //     */
    //    protected function opposingAccountExists(Validator $validator, string $type, ?int $accountId, ?string $accountName, string $idField): ?Account
    //    {
    //        /** @var User $admin */
    //        $admin       = auth()->user();
    //        $accountId   = (int)$accountId;
    //        $accountName = (string)$accountName;
    //        // both empty? done!
    //        if ($accountId < 1 && '' === $accountName) {
    //            return null;
    //        }
    //        if (0 !== $accountId) {
    //            // ID belongs to user and is $type account:
    //            /** @var AccountRepositoryInterface $repository */
    //            $repository = app(AccountRepositoryInterface::class);
    //            $repository->setUser($admin);
    //            $set = $repository->getAccountsById([$accountId]);
    //            if (1 === $set->count()) {
    //                /** @var Account $first */
    //                $first = $set->first();
    //                if ($first->accountType->type !== $type) {
    //                    $validator->errors()->add($idField, (string)trans('validation.belongs_user'));
    //
    //                    return null;
    //                }
    //
    //                // we ignore the account name at this point.
    //                return $first;
    //            }
    //        }
    //
    //        // not having an opposing account by this name is NOT a problem.
    //        return null;
    //    }
}
