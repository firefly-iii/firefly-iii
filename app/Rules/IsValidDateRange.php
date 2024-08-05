<?php
/*
 * IsValidDateRange.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Rules;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidDateRange implements ValidationRule
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value      = (string) $value;
        if ('' === $value) {
            $fail('validation.date_or_time')->translate();

            return;
        }
        $other      = 'startPeriod';
        if ('startPeriod' === $attribute) {
            $other = 'endPeriod';
        }
        $otherValue = request()->get($other);

        // parse date, twice.
        try {
            $left  = Carbon::parse($value);
            $right = Carbon::parse($otherValue);
        } catch (InvalidDateException $e) { // @phpstan-ignore-line
            app('log')->error(sprintf('"%s" or "%s" is not a valid date or time: %s', $value, $otherValue, $e->getMessage()));

            $fail('validation.date_or_time')->translate();

            return;
        } catch (InvalidFormatException $e) {
            app('log')->error(sprintf('"%s" or "%s" is of an invalid format: %s', $value, $otherValue, $e->getMessage()));

            $fail('validation.date_or_time')->translate();

            return;
        }
        // start must be before end.
        if ('startPeriod' === $attribute) {
            if ($left->gt($right)) {
                $fail('validation.date_after')->translate();
            }

            return;
        }
        // end must be after start
        if ($left->lt($right)) {
            $fail('validation.date_after')->translate();
        }
    }
}
