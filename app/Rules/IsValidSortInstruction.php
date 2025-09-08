<?php

declare(strict_types=1);
/*
 * IsValidSortInstruction.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidSortInstruction implements ValidationRule
{
    public function __construct(private readonly string $class) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $shortClass      = str_replace('FireflyIII\Models\\', '', $this->class);
        if (!is_string($value)) {
            $fail('validation.invalid_sort_instruction')->translate(['object' => $shortClass]);

            return;
        }
        $validParameters = config(sprintf('firefly.allowed_sort_parameters.%s', $shortClass));
        if (!is_array($validParameters)) {
            $fail('validation.no_sort_instructions')->translate(['object' => $shortClass]);

            return;
        }
        $parts           = explode(',', $value);
        foreach ($parts as $i => $part) {
            $part = trim($part);
            if (strlen($part) < 2) {
                $fail('validation.invalid_sort_instruction_index')->translate(['index' => $i, 'object' => $shortClass]);

                return;
            }
            if ('-' === $part[0]) {
                $part = substr($part, 1);
            }
            if (!in_array($part, $validParameters, true)) {
                $fail('validation.invalid_sort_instruction_index')->translate(['index' => $i, 'object' => $shortClass]);

                return;
            }
        }
    }
}
