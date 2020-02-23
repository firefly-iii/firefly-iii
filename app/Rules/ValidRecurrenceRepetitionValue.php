<?php
/**
 * ValidRecurrenceRepetitionValue.php
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

namespace FireflyIII\Rules;


use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use InvalidArgumentException;
use Log;

/**
 * Class ValidRecurrenceRepetitionValue
 * @codeCoverageIgnore
 */
class ValidRecurrenceRepetitionValue implements Rule
{

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return (string)trans('validation.valid_recurrence_rep_type');
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     *
     * @return bool
     *
     */
    public function passes($attribute, $value): bool
    {
        $value = (string)$value;

        if ('daily' === $value) {
            return true;
        }

        if (0 === strpos($value, 'monthly')) {
            return $this->validateMonthly($value);
        }

        // Value is like: ndom,3,7
        // nth x-day of the month.
        if (0 === strpos($value, 'ndom')) {
            return $this->validateNdom($value);
        }

        // Value is like: weekly,7
        if (0 === strpos($value, 'weekly')) {
            return $this->validateWeekly($value);
        }

        // Value is like: yearly,2018-01-01
        if (0 === strpos($value, 'yearly')) {
            return $this->validateYearly($value);
        }

        return false;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateMonthly(string $value): bool
    {
        $dayOfMonth = (int)substr($value, 8);

        return $dayOfMonth > 0 && $dayOfMonth < 32;
    }

    /**
     * @param string $value
     *
     * @return bool
     *
     */
    private function validateNdom(string $value): bool
    {
        $parameters = explode(',', substr($value, 5));
        if (2 !== count($parameters)) {
            return false;
        }
        $nthDay    = (int)($parameters[0] ?? 0.0);
        $dayOfWeek = (int)($parameters[1] ?? 0.0);
        if ($nthDay < 1 || $nthDay > 5) {
            return false;
        }

        return $dayOfWeek > 0 && $dayOfWeek < 8;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateWeekly(string $value): bool
    {
        $dayOfWeek = (int)substr($value, 7);

        return $dayOfWeek > 0 && $dayOfWeek < 8;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function validateYearly(string $value): bool
    {
        // rest of the string must be valid date:
        $dateString = substr($value, 7);
        try {
            Carbon::createFromFormat('Y-m-d', $dateString);
        } catch (InvalidArgumentException $e) {
            Log::debug(sprintf('Could not parse date %s: %s', $dateString, $e->getMessage()));

            return false;
        }

        return true;
    }
}
