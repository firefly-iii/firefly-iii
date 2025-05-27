<?php


/*
 * IsValidPositiveAmount.php
 * Copyright (c) 2025 james@firefly-iii.org.
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

use FireflyIII\Support\Validation\ValidatesAmountsTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Closure;

class IsValidPositiveAmount implements ValidationRule
{
    use ValidatesAmountsTrait;

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_array($value)) {
            $fail('validation.numeric')->translate();
            $message = sprintf('IsValidPositiveAmount: "%s" is not a number.', \Safe\json_encode($value));
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        $value = (string) $value;
        // must not be empty:
        if ($this->emptyString($value)) {
            $fail('validation.filled')->translate();
            $message = sprintf('IsValidPositiveAmount: "%s" cannot be empty.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }

        // must be a number:
        if (!$this->isValidNumber($value)) {
            $fail('validation.numeric')->translate();
            $message = sprintf('IsValidPositiveAmount: "%s" is not a number.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }
        // must not be scientific notation:
        if ($this->scientificNumber($value)) {
            $fail('validation.scientific_notation')->translate();
            $message = sprintf('IsValidPositiveAmount: "%s" cannot be in the scientific notation.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }
        // must be more than zero:
        if ($this->lessOrEqualToZero($value)) {
            $fail('validation.more_than_zero')->translate();
            $message = sprintf('IsValidPositiveAmount: "%s" must be more than zero.', $value);
            Log::debug($message);
            Log::channel('audit')->info($message);

            return;
        }
        // must be less than a large number
        if ($this->moreThanLots($value)) {
            $fail('validation.lte.numeric')->translate(['value' => self::BIG_AMOUNT]);
            $message = sprintf('IsValidPositiveAmount: "%s" must be less than %s.', $value, self::BIG_AMOUNT);
            Log::debug($message);
            Log::channel('audit')->info($message);
        }
        Log::debug(sprintf('IsValidPositiveAmount: "%s" is a valid positive amount.', $value));
    }
}
