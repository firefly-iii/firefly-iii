<?php
/**
 * ValidRecurrenceRepetitionValue.php
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

namespace FireflyIII\Rules;


use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use InvalidArgumentException;

/**
 * Class ValidRecurrenceRepetitionValue
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
     */
    public function passes($attribute, $value): bool
    {
        $value = (string)$value;

        if ($value === 'daily') {
            return true;
        }

        if (0 === strpos($value, 'monthly')) {
            $dayOfMonth = (int)substr($value, 8);

            return $dayOfMonth > 0 && $dayOfMonth < 32;
        }

        //ndom,3,7
        // nth x-day of the month.
        if (0 === strpos($value, 'ndom')) {
            $parameters = explode(',', substr($value, 5));
            if (\count($parameters) !== 2) {
                return false;
            }
            $nthDay    = (int)($parameters[0] ?? 0.0);
            $dayOfWeek = (int)($parameters[1] ?? 0.0);
            if ($nthDay < 1 || $nthDay > 5) {
                return false;
            }

            return $dayOfWeek > 0 && $dayOfWeek < 8;
        }

        //weekly,7
        if (0 === strpos($value, 'weekly')) {
            $dayOfWeek = (int)substr($value, 7);

            return $dayOfWeek > 0 && $dayOfWeek < 8;
        }

        //yearly,2018-01-01
        if (0 === strpos($value, 'yearly')) {
            // rest of the string must be valid date:
            $dateString = substr($value, 7);
            try {
                $date = Carbon::createFromFormat('Y-m-d', $dateString);
            } catch (InvalidArgumentException $e) {
                return false;
            }

            return true;
        }

        return false;
    }
}