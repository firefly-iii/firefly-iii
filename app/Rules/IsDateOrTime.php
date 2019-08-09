<?php

/**
 * IsDateOrTime.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use Carbon\Exceptions\InvalidDateException;
use Exception;
use Illuminate\Contracts\Validation\Rule;
use Log;

/**
 * Class IsDateOrTime
 */
class IsDateOrTime implements Rule
{

    /**
     * Get the validation error message.
     *
     * @return string|array
     * @codeCoverageIgnore
     */
    public function message()
    {
        return (string)trans('validation.date_or_time');
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
        if ('' === $value) {
            return false;
        }
        if (10 === strlen($value)) {
            // probably a date format.
            try {
                Carbon::createFromFormat('Y-m-d', $value);
            } catch (InvalidDateException|Exception $e) {
                Log::error(sprintf('"%s" is not a valid date: %s', $value, $e->getMessage()));

                return false;
            }

            return true;
        }
        // is an atom string, I hope?
        try {
            Carbon::parse($value);
        } catch (InvalidDateException|Exception $e) {
            Log::error(sprintf('"%s" is not a valid date or time: %s', $value, $e->getMessage()));

            return false;
        }

        return true;
    }
}
