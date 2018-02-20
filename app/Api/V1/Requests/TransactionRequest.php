<?php
/**
 * TransactionRequest.php
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

namespace FireflyIII\Api\V1\Requests;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Rules\BelongsUser;
use Illuminate\Validation\Validator;


/**
 * Class TransactionRequest
 */
class TransactionRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        // Only allow authenticated users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $data = [
            // basic fields for journal:
            'type'               => $this->string('type'),
            'date'               => $this->date('date'),
            'description'        => $this->string('description'),
            'piggy_bank_id'      => $this->integer('piggy_bank_id'),
            'piggy_bank_name'    => $this->string('piggy_bank_name'),
            'bill_id'            => $this->integer('bill_id'),
            'bill_name'          => $this->string('bill_name'),
            'tags'               => explode(',', $this->string('tags')),

            // then, custom fields for journal
            'interest_date'      => $this->date('interest_date'),
            'book_date'          => $this->date('book_date'),
            'process_date'       => $this->date('process_date'),
            'due_date'           => $this->date('due_date'),
            'payment_date'       => $this->date('payment_date'),
            'invoice_date'       => $this->date('invoice_date'),
            'internal_reference' => $this->string('internal_reference'),
            'notes'              => $this->string('notes'),

            // then, transactions (see below).
            'transactions'       => [],

        ];
        foreach ($this->get('transactions') as $index => $transaction) {
            $array                  = [
                'description'           => $transaction['description'] ?? null,
                'amount'                => $transaction['amount'],
                'currency_id'           => isset($transaction['currency_id']) ? intval($transaction['currency_id']) : null,
                'currency_code'         => isset($transaction['currency_code']) ? $transaction['currency_code'] : null,
                'foreign_amount'        => $transaction['foreign_amount'] ?? null,
                'foreign_currency_id'   => isset($transaction['foreign_currency_id']) ? intval($transaction['foreign_currency_id']) : null,
                'foreign_currency_code' => $transaction['foreign_currency_code'] ?? null,
                'budget_id'             => isset($transaction['budget_id']) ? intval($transaction['budget_id']) : null,
                'budget_name'           => $transaction['budget_name'] ?? null,
                'category_id'           => isset($transaction['category_id']) ? intval($transaction['category_id']) : null,
                'category_name'         => $transaction['category_name'] ?? null,
                'source_id'             => isset($transaction['source_id']) ? intval($transaction['source_id']) : null,
                'source_name'           => isset($transaction['source_name']) ? strval($transaction['source_name']) : null,
                'destination_id'        => isset($transaction['destination_id']) ? intval($transaction['destination_id']) : null,
                'destination_name'      => isset($transaction['destination_name']) ? strval($transaction['destination_name']) : null,
                'reconciled'            => $transaction['reconciled'] ?? false,
                'identifier'            => $index,
            ];
            $data['transactions'][] = $array;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            // basic fields for journal:
            'type'                                 => 'required|in:withdrawal,deposit,transfer',
            'date'                                 => 'required|date',
            'description'                          => 'between:1,255',
            'piggy_bank_id'                        => ['numeric', 'nullable', 'mustExist:piggy_banks,id', new BelongsUser],
            'piggy_bank_name'                      => ['between:1,255', 'nullable', new BelongsUser],
            'bill_id'                              => ['numeric', 'nullable', 'mustExist:bills,id', new BelongsUser],
            'bill_name'                            => ['between:1,255', 'nullable', new BelongsUser],
            'tags'                                 => 'between:1,255',

            // then, custom fields for journal
            'interest_date'                        => 'date|nullable',
            'book_date'                            => 'date|nullable',
            'process_date'                         => 'date|nullable',
            'due_date'                             => 'date|nullable',
            'payment_date'                         => 'date|nullable',
            'invoice_date'                         => 'date|nullable',
            'internal_reference'                   => 'min:1,max:255|nullable',
            'notes'                                => 'min:1,max:50000|nullable',

            // transaction rules (in array for splits):
            'transactions.*.description'           => 'nullable|between:1,255',
            'transactions.*.amount'                => 'required|numeric|more:0',
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id|required_without:transactions.*.currency_code',
            'transactions.*.currency_code'         => 'min:3|max:3|exists:transaction_currencies,code|required_without:transactions.*.currency_id',
            'transactions.*.foreign_amount'        => 'numeric|more:0',
            'transactions.*.foreign_currency_id'   => 'numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUser],
            'transactions.*.budget_name'           => ['between:1,255', 'nullable', new BelongsUser],
            'transactions.*.category_id'           => ['mustExist:categories,id', new BelongsUser],
            'transactions.*.category_name'         => 'between:1,255|nullable',
            'transactions.*.reconciled'            => 'boolean|nullable',
            // basic rules will be expanded later.
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.source_name'           => 'between:1,255|nullable',
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.destination_name'      => 'between:1,255|nullable',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(
            function (Validator $validator) {
                $this->atLeastOneTransaction($validator);
                $this->checkValidDescriptions($validator);
                $this->equalToJournalDescription($validator);
                $this->emptySplitDescriptions($validator);
                $this->foreignCurrencyInformation($validator);
                $this->validateAccountInformation($validator);
                $this->validateSplitAccounts($validator);
            }
        );
    }

    /**
     * Throws an error when this asset account is invalid.
     *
     * @param Validator   $validator
     * @param int|null    $accountId
     * @param null|string $accountName
     * @param string      $idField
     * @param string      $nameField
     */
    protected function assetAccountExists(Validator $validator, ?int $accountId, ?string $accountName, string $idField, string $nameField): void
    {
        $accountId   = intval($accountId);
        $accountName = strval($accountName);
        // both empty? hard exit.
        if ($accountId < 1 && strlen($accountName) === 0) {
            $validator->errors()->add($idField, trans('validation.filled', ['attribute' => $idField]));

            return;
        }
        // ID belongs to user and is asset account:
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $set = $repository->getAccountsById([$accountId]);
        if ($set->count() === 1) {
            /** @var Account $first */
            $first = $set->first();
            if ($first->accountType->type !== AccountType::ASSET) {
                $validator->errors()->add($idField, trans('validation.belongs_user'));

                return;
            }

            // we ignore the account name at this point.
            return;
        }
        $account = $repository->findByName($accountName, [AccountType::ASSET]);
        if (is_null($account)) {
            $validator->errors()->add($nameField, trans('validation.belongs_user'));
        }

        return;
    }

    /**
     * Adds an error to the validator when there are no transactions in the array of data.
     *
     * @param Validator $validator
     */
    protected function atLeastOneTransaction(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        // need at least one transaction
        if (count($transactions) === 0) {
            $validator->errors()->add('description', trans('validation.at_least_one_transaction'));
        }
    }

    /**
     * Adds an error to the "description" field when the user has submitted no descriptions and no
     * journal description.
     *
     * @param Validator $validator
     */
    protected function checkValidDescriptions(Validator $validator)
    {
        $data               = $validator->getData();
        $transactions       = $data['transactions'] ?? [];
        $journalDescription = strval($data['description'] ?? '');
        $validDescriptions  = 0;
        foreach ($transactions as $index => $transaction) {
            if (strlen(strval($transaction['description'] ?? '')) > 0) {
                $validDescriptions++;
            }
        }

        // no valid descriptions and empty journal description? error.
        if ($validDescriptions === 0 && strlen($journalDescription) === 0) {
            $validator->errors()->add('description', trans('validation.filled', ['attribute' => trans('validation.attributes.description')]));
        }

    }

    /**
     * Adds an error to the validator when the user submits a split transaction (more than 1 transactions)
     * but does not give them a description.
     *
     * @param Validator $validator
     */
    protected function emptySplitDescriptions(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        foreach ($transactions as $index => $transaction) {
            $description = strval($transaction['description'] ?? '');
            // filled description is mandatory for split transactions.
            if (count($transactions) > 1 && strlen($description) === 0) {
                $validator->errors()->add(
                    'transactions.' . $index . '.description',
                    trans('validation.filled', ['attribute' => trans('validation.attributes.transaction_description')])
                );
            }
        }
    }

    /**
     * Adds an error to the validator when any transaction descriptions are equal to the journal description.
     *
     * @param Validator $validator
     */
    protected function equalToJournalDescription(Validator $validator): void
    {
        $data               = $validator->getData();
        $transactions       = $data['transactions'] ?? [];
        $journalDescription = strval($data['description'] ?? '');
        foreach ($transactions as $index => $transaction) {
            $description = strval($transaction['description'] ?? '');
            // description cannot be equal to journal description.
            if ($description === $journalDescription) {
                $validator->errors()->add('transactions.' . $index . '.description', trans('validation.equal_description'));
            }
        }
    }

    /**
     * If the transactions contain foreign amounts, there must also be foreign currency information.
     *
     * @param Validator $validator
     */
    protected function foreignCurrencyInformation(Validator $validator): void
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
                    trans('validation.require_currency_info')
                );
            }
        }
    }

    /**
     * Throws an error when the given opping account (of type $type) is invalid.
     * Empty data is allowed, system will default to cash.
     *
     * @param Validator   $validator
     * @param string      $type
     * @param int|null    $accountId
     * @param null|string $accountName
     * @param string      $idField
     * @param string      $nameField
     */
    protected function opposingAccountExists(Validator $validator, string $type, ?int $accountId, ?string $accountName, string $idField, string $nameField
    ): void {
        $accountId   = intval($accountId);
        $accountName = strval($accountName);
        // both empty? done!
        if ($accountId < 1 && strlen($accountName) === 0) {
            return;
        }
        // ID belongs to user and is $type account:
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $repository->setUser(auth()->user());
        $set = $repository->getAccountsById([$accountId]);
        if ($set->count() === 1) {
            /** @var Account $first */
            $first = $set->first();
            if ($first->accountType->type !== $type) {
                $validator->errors()->add($idField, trans('validation.belongs_user'));

                return;
            }

            // we ignore the account name at this point.
            return;
        }

        // not having an opposing account by this name is NOT a problem.
        return;
    }

    /**
     * Validates the given account information. Switches on given transaction type.
     *
     * @param Validator $validator
     *
     * @throws FireflyException
     */
    protected function validateAccountInformation(Validator $validator): void
    {
        $data         = $validator->getData();
        $transactions = $data['transactions'] ?? [];
        if(!isset($data['type'])) {
            return;
        }
        foreach ($transactions as $index => $transaction) {

            $sourceId        = isset($transaction['source_id']) ? intval($transaction['source_id']) : null;
            $sourceName      = $transaction['source_name'] ?? null;
            $destinationId   = isset($transaction['destination_id']) ? intval($transaction['destination_id']) : null;
            $destinationName = $transaction['destination_name'] ?? null;

            switch ($data['type']) {
                case 'withdrawal':
                    $idField   = 'transactions.' . $index . '.source_id';
                    $nameField = 'transactions.' . $index . '.source_name';
                    $this->assetAccountExists($validator, $sourceId, $sourceName, $idField, $nameField);

                    $idField   = 'transactions.' . $index . '.destination_id';
                    $nameField = 'transactions.' . $index . '.destination_name';
                    $this->opposingAccountExists($validator, AccountType::EXPENSE, $destinationId, $destinationName, $idField, $nameField);
                    break;
                case 'deposit':
                    $idField   = 'transactions.' . $index . '.source_id';
                    $nameField = 'transactions.' . $index . '.source_name';
                    $this->opposingAccountExists($validator, AccountType::REVENUE, $sourceId, $sourceName, $idField, $nameField);

                    $idField   = 'transactions.' . $index . '.destination_id';
                    $nameField = 'transactions.' . $index . '.destination_name';
                    $this->assetAccountExists($validator, $destinationId, $destinationName, $idField, $nameField);
                    break;
                case 'transfer':
                    $idField   = 'transactions.' . $index . '.source_id';
                    $nameField = 'transactions.' . $index . '.source_name';
                    $this->assetAccountExists($validator, $sourceId, $sourceName, $idField, $nameField);

                    $idField   = 'transactions.' . $index . '.destination_id';
                    $nameField = 'transactions.' . $index . '.destination_name';
                    $this->assetAccountExists($validator, $destinationId, $destinationName, $idField, $nameField);
                    break;
                default:
                    throw new FireflyException(sprintf('The validator cannot handle transaction type "%s" in validateAccountInformation().', $data['type']));

            }
        }
    }

    /**
     * @param Validator $validator
     *
     * @throws FireflyException
     */
    protected function validateSplitAccounts(Validator $validator)
    {
        $data  = $validator->getData();
        $count = isset($data['transactions']) ? count($data['transactions']) : 0;
        if ($count < 2) {
            return;
        }
        // collect all source ID's and destination ID's, if present:
        $sources      = [];
        $destinations = [];

        foreach ($data['transactions'] as $transaction) {
            $sources[]      = isset($transaction['source_id']) ? intval($transaction['source_id']) : 0;
            $destinations[] = isset($transaction['destination_id']) ? intval($transaction['destination_id']) : 0;
        }
        $destinations = array_unique($destinations);
        $sources      = array_unique($sources);
        // switch on type:
        switch ($data['type']) {
            case 'withdrawal':
                if (count($sources) > 1) {
                    $validator->errors()->add('transactions.0.source_id', trans('validation.all_accounts_equal'));
                }
                break;
            case 'deposit':
                if (count($destinations) > 1) {
                    $validator->errors()->add('transactions.0.destination_id', trans('validation.all_accounts_equal'));
                }
                break;
            case 'transfer':
                if (count($sources) > 1 || count($destinations) > 1) {
                    $validator->errors()->add('transactions.0.source_id', trans('validation.all_accounts_equal'));
                    $validator->errors()->add('transactions.0.destination_id', trans('validation.all_accounts_equal'));
                }
                break;
            default:
                throw new FireflyException(sprintf('The validator cannot handle transaction type "%s" in validateSplitAccounts().', $data['type']));
        }

        return;
    }

}