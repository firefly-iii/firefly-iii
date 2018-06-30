<?php
/**
 * RecurrenceRequest.php
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

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Rules\BelongsUser;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * Class RecurrenceRequest
 */
class RecurrenceRequest extends Request
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
        $return = [
            'recurrence'   => [
                'type'         => $this->string('type'),
                'title'        => $this->string('title'),
                'description'  => $this->string('description'),
                'first_date'   => $this->date('first_date'),
                'repeat_until' => $this->date('repeat_until'),
                'repetitions'  => $this->integer('nr_of_repetitions'),
                'apply_rules'  => $this->boolean('apply_rules'),
                'active'       => $this->boolean('active'),
            ],
            'meta'         => [
                'piggy_bank_id'   => $this->integer('piggy_bank_id'),
                'piggy_bank_name' => $this->string('piggy_bank_name'),
                'tags'            => explode(',', $this->string('tags')),
            ],
            'transactions' => [],
            'repetitions'  => [],
        ];

        // repetition data:
        /** @var array $repetitions */
        $repetitions = $this->get('repetitions');
        /** @var array $repetition */
        foreach ($repetitions as $repetition) {
            $return['repetitions'][] = [
                'type'    => $repetition['type'],
                'moment'  => $repetition['moment'],
                'skip'    => (int)$repetition['skip'],
                'weekend' => (int)$repetition['weekend'],
            ];
        }
        // transaction data:
        /** @var array $transactions */
        $transactions = $this->get('transactions');
        /** @var array $transaction */
        foreach ($transactions as $transaction) {
            $return['transactions'][] = [
                'amount' => $transaction['amount'],

                'currency_id'   => isset($transaction['currency_id']) ? (int)$transaction['currency_id'] : null,
                'currency_code' => $transaction['currency_code'] ?? null,

                'foreign_amount'        => $transaction['foreign_amount'] ?? null,
                'foreign_currency_id'   => isset($transaction['foreign_currency_id']) ? (int)$transaction['foreign_currency_id'] : null,
                'foreign_currency_code' => $transaction['foreign_currency_code'] ?? null,

                'budget_id'     => isset($transaction['budget_id']) ? (int)$transaction['budget_id'] : null,
                'budget_name'   => $transaction['budget_name'] ?? null,
                'category_id'   => isset($transaction['category_id']) ? (int)$transaction['category_id'] : null,
                'category_name' => $transaction['category_name'] ?? null,

                'source_id'        => isset($transaction['source_id']) ? (int)$transaction['source_id'] : null,
                'source_name'      => isset($transaction['source_name']) ? (string)$transaction['source_name'] : null,
                'destination_id'   => isset($transaction['destination_id']) ? (int)$transaction['destination_id'] : null,
                'destination_name' => isset($transaction['destination_name']) ? (string)$transaction['destination_name'] : null,

                'description' => $transaction['description'],
            ];
        }

        return $return;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        $today = new Carbon;
        $today->addDay();

        return [
            'type'                                 => 'required|in:withdrawal,transfer,deposit',
            'title'                                => 'required|between:1,255',
            'description'                          => 'between:1,65000',
            'first_date'                           => sprintf('required|date|after:%s', $today->format('Y-m-d')),
            'repeat_until'                         => sprintf('date|after:%s', $today->format('Y-m-d')),
            'nr_of_repetitions'                    => 'numeric|between:1,31',
            'apply_rules'                          => 'required|boolean',
            'active'                               => 'required|boolean',

            // rules for meta values:
            'tags'                                 => 'between:1,64000',
            'piggy_bank_id'                        => 'numeric',

            // rules for repetitions.
            'repetitions.*.type'                   => 'required|in:daily,weekly,ndom,monthly,yearly',
            'repetitions.*.moment'                 => 'between:0,10',
            'repetitions.*.skip'                   => 'required|between:0,31',
            'repetitions.*.weekend'                => 'required|between:1,4',

            // rules for transactions.
            'transactions.*.currency_id'           => 'numeric|exists:transaction_currencies,id|required_without:transactions.*.currency_code',
            'transactions.*.currency_code'         => 'min:3|max:3|exists:transaction_currencies,code|required_without:transactions.*.currency_id',
            'transactions.*.foreign_amount'        => 'numeric|more:0',
            'transactions.*.foreign_currency_id'   => 'numeric|exists:transaction_currencies,id',
            'transactions.*.foreign_currency_code' => 'min:3|max:3|exists:transaction_currencies,code',
            'transactions.*.budget_id'             => ['mustExist:budgets,id', new BelongsUser],
            'transactions.*.category_name'         => 'between:1,255|nullable',
            'transactions.*.source_id'             => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.source_name'           => 'between:1,255|nullable',
            'transactions.*.destination_id'        => ['numeric', 'nullable', new BelongsUser],
            'transactions.*.destination_name'      => 'between:1,255|nullable',
            'transactions.*.amount'                => 'required|numeric|more:0',
            'transactions.*.description'           => 'required|between:1,255',
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
                $this->atLeastOneRepetition($validator);
                $this->validRepeatsUntil($validator);
                $this->validRepetitionMoment($validator);
                $this->foreignCurrencyInformation($validator);
                $this->validateAccountInformation($validator);
            }
        );
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
        $accountId   = (int)$accountId;
        $accountName = (string)$accountName;
        // both empty? hard exit.
        if ($accountId < 1 && '' === $accountName) {
            $validator->errors()->add($idField, trans('validation.filled', ['attribute' => $idField]));

            return null;
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

                return null;
            }

            // we ignore the account name at this point.
            return $first;
        }

        $account = $repository->findByName($accountName, [AccountType::ASSET]);
        if (null === $account) {
            $validator->errors()->add($nameField, trans('validation.belongs_user'));

            return null;
        }

        return $account;
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     *
     * @param Validator $validator
     */
    protected function atLeastOneRepetition(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? [];
        // need at least one transaction
        if (\count($repetitions) === 0) {
            $validator->errors()->add('description', trans('validation.at_least_one_repetition'));
        }
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
        if (\count($transactions) === 0) {
            $validator->errors()->add('description', trans('validation.at_least_one_transaction'));
        }
    }

    /**
     * TODO can be made a rule?
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
        $accountId   = (int)$accountId;
        $accountName = (string)$accountName;
        // both empty? done!
        if ($accountId < 1 && \strlen($accountName) === 0) {
            return null;
        }
        if ($accountId !== 0) {
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

                    return null;
                }

                // we ignore the account name at this point.
                return $first;
            }
        }

        // not having an opposing account by this name is NOT a problem.
        return null;
    }

    /**
     * TODO can be a rule?
     *
     * Validates the given account information. Switches on given transaction type.
     *
     * @param Validator $validator
     */
    protected function validateAccountInformation(Validator $validator): void
    {
        $data            = $validator->getData();
        $transactions    = $data['transactions'] ?? [];
        $idField         = 'description';
        $transactionType = $data['type'] ?? 'false';
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
                    $validator->errors()->add($idField, trans('validation.invalid_account_info'));

                    return;

            }
            // add some errors in case of same account submitted:
            if (null !== $sourceAccount && null !== $destinationAccount && $sourceAccount->id === $destinationAccount->id) {
                $validator->errors()->add($idField, trans('validation.source_equals_destination'));
            }
        }
    }

    /**
     * @param Validator $validator
     */
    private function validRepeatsUntil(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['nr_of_repetitions'] ?? null;
        $repeatUntil = $data['repeat_until'] ?? null;
        if (null !== $repetitions && null !== $repeatUntil) {
            // expect a date OR count:
            $validator->errors()->add('repeat_until', trans('validation.require_repeat_until'));
            $validator->errors()->add('repetitions', trans('validation.require_repeat_until'));

            return;
        }
    }

    /**
     * TODO merge this in a rule somehow.
     *
     * @param Validator $validator
     */
    private function validRepetitionMoment(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? [];
        /**
         * @var int   $index
         * @var array $repetition
         */
        foreach ($repetitions as $index => $repetition) {
            switch ($repetition['type']) {
                default:
                    $validator->errors()->add(sprintf('repetitions.%d.type', $index), trans('validation.valid_recurrence_rep_type'));

                    return;
                case 'daily':
                    if ('' !== (string)$repetition['moment']) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));
                    }

                    return;
                case 'monthly':
                    $dayOfMonth = (int)$repetition['moment'];
                    if ($dayOfMonth < 1 || $dayOfMonth > 31) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));
                    }

                    return;
                case 'ndom':
                    $parameters = explode(',', $repetition['moment']);
                    if (\count($parameters) !== 2) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));

                        return;
                    }
                    $nthDay    = (int)($parameters[0] ?? 0.0);
                    $dayOfWeek = (int)($parameters[1] ?? 0.0);
                    if ($nthDay < 1 || $nthDay > 5) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));

                        return;
                    }
                    if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));

                        return;
                    }

                    return;
                case 'weekly':
                    $dayOfWeek = (int)$repetition['moment'];
                    if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));

                        return;
                    }
                    break;
                case 'yearly':
                    try {
                        Carbon::createFromFormat('Y-m-d', $repetition['moment']);
                    } catch (InvalidArgumentException $e) {
                        $validator->errors()->add(sprintf('repetitions.%d.moment', $index), trans('validation.valid_recurrence_rep_moment'));

                        return;
                    }
            }
        }
    }
}