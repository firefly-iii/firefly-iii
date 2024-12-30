<?php

/**
 * IsDateOrTime.php
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
use Carbon\Exceptions\InvalidDateException;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Class IsDateOrTime
 */
class IsDateOrTime implements ValidationRule
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = (string) $value;
        if ('' === $value) {
            $fail('validation.date_or_time')->translate();

            return;
        }
        if (10 === strlen($value)) {
            // probably a date format.
            try {
                Carbon::createFromFormat('Y-m-d', $value);
            } catch (InvalidDateException $e) { // @phpstan-ignore-line
                app('log')->error(sprintf('"%s" is not a valid date: %s', $value, $e->getMessage()));

                $fail('validation.date_or_time')->translate();

                return;
            } catch (InvalidFormatException $e) { // @phpstan-ignore-line
                app('log')->error(sprintf('"%s" is of an invalid format: %s', $value, $e->getMessage()));

                $fail('validation.date_or_time')->translate();

                return;
            }

            return;
        }

        // is an atom string, I hope?
        try {
            Carbon::parse($value);
        } catch (InvalidDateException $e) { // @phpstan-ignore-line
            app('log')->error(sprintf('"%s" is not a valid date or time: %s', $value, $e->getMessage()));

            $fail('validation.date_or_time')->translate();

            return;
        } catch (InvalidFormatException $e) {
            app('log')->error(sprintf('"%s" is of an invalid format: %s', $value, $e->getMessage()));

            $fail('validation.date_or_time')->translate();

            return;
        }
    }
}
