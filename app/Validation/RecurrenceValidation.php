<?php
/**
 * ApiValidation.php
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

use Carbon\Carbon;
use Exception;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Log;

/**
 * Trait RecurrenceValidation
 *
 * Contains advanced validation rules used in validation of new and existing recurrences.
 *
 */
trait RecurrenceValidation
{


    /**
     * Validate account information input for recurrences which are being updated.
     *
     * TODO must always trigger when the type of the recurrence changes.
     *
     * @param Validator $validator
     */
    public function valUpdateAccountInfo(Validator $validator): void
    {
        //Log::debug('Now in validateAccountInformation()');
        $data = $validator->getData();

        $transactionType = $data['type'] ?? 'invalid';
        $transactions    = $data['transactions'] ?? [];

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
     * Adds an error to the validator when there are no repetitions in the array of data.
     *
     * @param Validator $validator
     */
    public function validateOneRepetition(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? [];
        // need at least one transaction
        if (0 === count($repetitions)) {
            $validator->errors()->add('repetitions', (string)trans('validation.at_least_one_repetition'));
        }
    }

    /**
     * Adds an error to the validator when there are no repetitions in the array of data.
     *
     * @param Validator $validator
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
     *
     * @param Validator $validator
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

    /**
     * @param Validator $validator
     */
    public function validateRepetitionMoment(Validator $validator): void
    {
        $data        = $validator->getData();
        $repetitions = $data['repetitions'] ?? [];
        /**
         * @var int   $index
         * @var array $repetition
         */
        foreach ($repetitions as $index => $repetition) {
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
     *
     * @param Validator $validator
     * @param int       $index
     * @param string    $moment
     */
    protected function validateDaily(Validator $validator, int $index, string $moment): void
    {
        if ('' !== $moment) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * If the repetition type is monthly, the moment should be a day between 1-31 (inclusive).
     *
     * @param Validator $validator
     * @param int       $index
     * @param int       $dayOfMonth
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
     *
     * @param Validator $validator
     * @param int       $index
     * @param string    $moment
     */
    protected function validateNdom(Validator $validator, int $index, string $moment): void
    {
        $parameters = explode(',', $moment);
        if (2 !== count($parameters)) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));

            return;
        }
        $nthDay    = (int)($parameters[0] ?? 0.0);
        $dayOfWeek = (int)($parameters[1] ?? 0.0);
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
     *
     * @param Validator $validator
     * @param int       $index
     * @param int       $dayOfWeek
     */
    protected function validateWeekly(Validator $validator, int $index, int $dayOfWeek): void
    {
        if ($dayOfWeek < 1 || $dayOfWeek > 7) {
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }

    /**
     * If the repetition type is yearly, the moment should be a valid date.
     *
     * @param Validator $validator
     * @param int       $index
     * @param string    $moment
     */
    protected function validateYearly(Validator $validator, int $index, string $moment): void
    {
        try {
            Carbon::createFromFormat('Y-m-d', $moment);
        } catch (InvalidArgumentException|Exception $e) {
            Log::debug(sprintf('Invalid argument for Carbon: %s', $e->getMessage()));
            $validator->errors()->add(sprintf('repetitions.%d.moment', $index), (string)trans('validation.valid_recurrence_rep_moment'));
        }
    }
}
