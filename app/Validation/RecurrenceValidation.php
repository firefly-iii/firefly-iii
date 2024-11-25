<?php

/**
 * RecurrenceValidation.php
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

use Carbon\Carbon;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use Illuminate\Validation\Validator;

/**
 * Trait RecurrenceValidation
 *
 * Contains advanced validation rules used in validation of new and existing recurrences.
 */
trait RecurrenceValidation
{
    /**
     * Validate account information input for recurrences which are being updated.
     *
     * TODO Must always trigger when the type of the recurrence changes.
     */
    public function valUpdateAccountInfo(Validator $validator): void
    {
        $data             = $validator->getData();

        $transactionType  = $data['type'] ?? 'invalid';

        // grab model from parameter and try to set the transaction type from it
        if ('invalid' === $transactionType) {
            app('log')->debug('Type is invalid but we will search for it.');

            /** @var null|Recurrence $recurrence */
            $recurrence = $this->route()?->parameter('recurrence');
            if (null !== $recurrence) {
                app('log')->debug('There is a recurrence in the route.');

                // ok so we have a recurrence should be able to extract type somehow.
                /** @var null|RecurrenceTransaction $first */
                $first = $recurrence->recurrenceTransactions()->first();
                if (null !== $first) {
                    $transactionType = null !== $first->transactionType ? $first->transactionType->type : 'withdrawal';
                    app('log')->debug(sprintf('Determined type to be %s.', $transactionType));
                }
                if (null === $first) {
                    app('log')->warning('Just going to assume type is a withdrawal.');
                    $transactionType = 'withdrawal';
                }
            }
        }

        $transactions     = $data['transactions'] ?? [];

        /** @var AccountValidator $accountValidator */
        $accountValidator = app(AccountValidator::class);

        app('log')->debug(sprintf('Going to loop %d transaction(s)', count($transactions)));
        foreach ($transactions as $index => $transaction) {
            $transactionType  = $transaction['type'] ?? $transactionType;
            $accountValidator->setTransactionType($transactionType);

            if (
                !array_key_exists('source_id', $transaction)
                && !array_key_exists('destination_id', $transaction)
                && !array_key_exists('source_name', $transaction)
                && !array_key_exists('destination_name', $transaction)
            ) {
                continue;
            }
            // validate source account.
            $sourceId         = array_key_exists('source_id', $transaction) ? (int)$transaction['source_id'] : null;
            $sourceName       = $transaction['source_name'] ?? null;
            $validSource      = $accountValidator->validateSource(['id' => $sourceId, 'name' => $sourceName]);

            // do something with result:
            if (false === $validSource) {
                $validator->errors()->add(sprintf('transactions.%d.source_id', $index), $accountValidator->sourceError);
                $validator->errors()->add(sprintf('transactions.%d.source_name', $index), $accountValidator->sourceError);

                return;
            }
            // validate destination account
            $destinationId    = array_key_exists('destination_id', $transaction) ? (int)$transaction['destination_id'] : null;
            $destinationName  = $transaction['destination_name'] ?? null;
            $validDestination = $accountValidator->validateDestination(['id' => $destinationId, 'name' => $destinationName]);
            // do something with result:
            if (false === $validDestination) {
                $validator->errors()->add(sprintf('transactions.%d.destination_id', $index), $accountValidator->destError);
                $validator->errors()->add(sprintf('transactions.%d.destination_name', $index), $accountValidator->destError);

                return;
            }
        }
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     */
    public function validateOneRepetition(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? [];
        // need at least one transaction
        if (!is_countable($repetitions) || 0 === count($repetitions)) {
            $validator->errors()->add('repetitions', (string)trans('validation.at_least_one_repetition'));
        }
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     */
    public function validateOneRepetitionUpdate(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? null;
        if (null === $repetitions) {
            return;
        }
        // need at least one transaction
        if (0 === count($repetitions)) {
            $validator->errors()->add('repetitions', (string)trans('validation.at_least_one_repetition'));
        }
    }

    /**
     * Validates that the recurrence has valid repetition information. It either doesn't stop,
     * or stops after X times or at X date. Not both of them.,
     */
    public function validateRecurrenceRepetition(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['nr_of_repetitions'] ?? null;
        $repeatUntil = $data['repeat_until'] ?? null;
        if (null !== $repetitions && null !== $repeatUntil) {
            // expect a date OR count:
            $validator->errors()->add('repeat_until', (string)trans('validation.require_repeat_until'));
            $validator->errors()->add('nr_of_repetitions', (string)trans('validation.require_repeat_until'));
        }
    }

    public function validateRecurringConfig(Validator $validator): void
    {
        $data        = $validator->getData();
        $reps        = array_key_exists('nr_of_repetitions', $data) ? (int)$data['nr_of_repetitions'] : null;
        $repeatUntil = array_key_exists('repeat_until', $data) ? new Carbon($data['repeat_until']) : null;

        if (null === $reps && null === $repeatUntil) {
            $validator->errors()->add('nr_of_repetitions', trans('validation.require_repeat_until'));
            $validator->errors()->add('repeat_until', trans('validation.require_repeat_until'));

            return;
        }
        if ($reps > 0 && null !== $repeatUntil) {
            $validator->errors()->add('nr_of_repetitions', trans('validation.require_repeat_until'));
            $validator->errors()->add('repeat_until', trans('validation.require_repeat_until'));
        }
    }

    public function validateRepetitionMoment(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? [];
        if (!is_array($repetitions)) {
            $validator->errors()->add(sprintf('repetitions.%d.type', 0), (string)trans('validation.valid_recurrence_rep_type'));

            return;
        }

        /**
         * @var int   $index
         * @var array $repetition
         */
        foreach ($repetitions as $index => $repetition) {
            if (!array_key_exists('moment', $repetition)) {
                $repetition['moment'] = '';
            }
            if (null === $repetition['moment']) {
                $repetition['moment'] = '';
            }

            switch ($repetition['type'] ?? 'empty') {
                default:
                    $validator->errors()->add(sprintf('repetitions.%d.type', $index), (string)trans('validation.valid_recurrence_rep_type'));

                    return;

                case 'daily':
                    $this->validateDaily($validator, $index, (string)$repetition['moment']);

                    break;

                case 'monthly':
                    $this->validateMonthly($validator, $index, (int)$repetition['moment']);

                    break;

                case 'ndom':
                    $this->validateNdom($validator, $index, (string)$repetition['moment']);

                    break;

                case 'weekly':
                    $this->validateWeekly($validator, $index, (int)$repetition['moment']);

                    break;

                case 'yearly':
                    $this->validateYearly($validator, $index, (string)$repetition['moment']);

                    break;
            }
        }
    }

    /**
     * If the repetition type is daily, the moment should be empty.
     */
    protected function validateDaily(Validator $validator, int $index, string $moment): void
    {
        if ('' !== $moment) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * If the repetition type is monthly, the moment should be a day between 1-31 (inclusive).
     */
    protected function validateMonthly(Validator $validator, int $index, int $dayOfMonth): void
    {
        if ($dayOfMonth < 1 || $dayOfMonth > 31) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * If the repetition type is "ndom", the first part must be between 1-5 (inclusive), for the week in the month,
     * and the second one must be between 1-7 (inclusive) for the day of the week.
     */
    protected function validateNdom(Validator $validator, int $index, string $moment): void
    {
        $parameters = explode(',', $moment);
        if (2 !== count($parameters)) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));

            return;
        }
        $nthDay     = (int)($parameters[0] ?? 0.0);
        $dayOfWeek  = (int)($parameters[1] ?? 0.0);
        if ($nthDay < 1 || $nthDay > 5) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));

            return;
        }
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * If the repetition type is weekly, the moment should be a day between 1-7 (inclusive).
     */
    protected function validateWeekly(Validator $validator, int $index, int $dayOfWeek): void
    {
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * If the repetition type is yearly, the moment should be a valid date.
     */
    protected function validateYearly(Validator $validator, int $index, string $moment): void
    {
        try {
            Carbon::createFromFormat('Y-m-d', $moment);
        } catch (\InvalidArgumentException $e) { // @phpstan-ignore-line
            app('log')->debug(sprintf('Invalid argument for Carbon: %s', $e->getMessage()));
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validateTransactionId(Recurrence $recurrence, Validator $validator): void
    {
        app('log')->debug('Now in validateTransactionId');
        $transactions     = $this->getTransactionData();
        $submittedTrCount = count($transactions);

        if (0 === $submittedTrCount) {
            app('log')->warning('[b] User submitted no transactions.');
            $validator->errors()->add('transactions', (string)trans('validation.at_least_one_transaction'));

            return;
        }
        $originalTrCount  = $recurrence->recurrenceTransactions()->count();
        if (1 === $submittedTrCount && 1 === $originalTrCount) {
            $first       = $transactions[0]; // can safely assume index 0.
            if (!array_key_exists('id', $first)) {
                app('log')->debug('Single count and no ID, done.');

                return; // home safe!
            }
            $id          = $first['id'];
            if ('' === (string)$id) {
                app('log')->debug('Single count and empty ID, done.');

                return; // home safe!
            }
            $integer     = (int)$id;
            $secondCount = $recurrence->recurrenceTransactions()->where('recurrences_transactions.id', $integer)->count();
            app('log')->debug(sprintf('Result of ID count: %d', $secondCount));
            if (0 === $secondCount) {
                $validator->errors()->add('transactions.0.id', (string)trans('validation.id_does_not_match', ['id' => $integer]));
            }
            app('log')->debug('Single ID validation done.');

            return;
        }

        app('log')->debug('Multi ID validation.');
        $idsMandatory     = false;
        if ($submittedTrCount < $originalTrCount) {
            app('log')->debug(sprintf('User submits %d transaction, recurrence has %d transactions. All entries must have ID.', $submittedTrCount, $originalTrCount));
            $idsMandatory = true;
        }

        /**
         * Loop all transactions submitted by the user.
         * If the user has submitted fewer transactions than the original recurrence has, all submitted entries must have an ID.
         * Any ID's missing will be deleted later on.
         *
         * If the user submits more or the same number of transactions (n), the following rules apply:
         *
         * 1. Any 1 transaction does not need to have an ID. Since the other n-1 can be matched, the last one can be assumed.
         * 2. If the user submits more transactions than already present, count the number of existing transactions. At least those must be matched. After that, submit as many as you like.
         * 3. If the user submits the same number of transactions as already present, all but one must have an ID.
         */
        $unmatchedIds     = 0;

        foreach ($transactions as $index => $transaction) {
            app('log')->debug(sprintf('Now at %d/%d', $index + 1, $submittedTrCount));
            if (!is_array($transaction)) {
                app('log')->warning('Not an array. Give error.');
                $validator->errors()->add(sprintf('transactions.%d.id', $index), (string)trans('validation.at_least_one_transaction'));

                return;
            }
            if (!array_key_exists('id', $transaction) && $idsMandatory) {
                app('log')->warning('ID is mandatory but array has no ID.');
                $validator->errors()->add(sprintf('transactions.%d.id', $index), (string)trans('validation.need_id_to_match'));

                return;
            }
            if (array_key_exists('id', $transaction)) { // don't matter if $idsMandatory
                app('log')->debug('Array has ID.');
                $idCount = $recurrence->recurrenceTransactions()->where('recurrences_transactions.id', (int)$transaction['id'])->count();
                if (0 === $idCount) {
                    app('log')->debug('ID does not exist or no match. Count another unmatched ID.');
                    ++$unmatchedIds;
                }
            }
            if (!array_key_exists('id', $transaction) && !$idsMandatory) {
                app('log')->debug('Array has no ID but was not mandatory at this point.');
                ++$unmatchedIds;
            }
        }
        // if too many don't match, but you haven't submitted more than already present:
        $maxUnmatched     = max(1, $submittedTrCount - $originalTrCount);
        app('log')->debug(sprintf('Submitted: %d. Original: %d. User can submit %d unmatched transactions.', $submittedTrCount, $originalTrCount, $maxUnmatched));
        if ($unmatchedIds > $maxUnmatched) {
            app('log')->warning(sprintf('Too many unmatched transactions (%d).', $unmatchedIds));
            $validator->errors()->add('transactions.0.id', (string)trans('validation.too_many_unmatched'));

            return;
        }
        app('log')->debug('Done with ID validation.');
    }
}
