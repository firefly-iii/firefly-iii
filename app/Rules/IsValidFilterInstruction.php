<?php

declare(strict_types=1);

/*
 * IsValidFilterInstructions.php
 * Copyright (c) 2026 james@firefly-iii.org
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
use Override;

class IsValidFilterInstruction implements ValidationRule
{
    public function __construct(
        private readonly string $class
    ) {}

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }
        if (0 === count($value)) {
            return;
        }
        $shortClass      = str_replace('FireflyIII\Models\\', '', $this->class);
        $validParameters = config(sprintf('firefly.allowed_filter_parameters.%s', $shortClass));
        if (!is_array($validParameters)) {
            $fail('validation.no_filter_instructions')->translate(['object' => $shortClass]);

            return;
        }
        foreach ($value as $key => $search) {
            $search = trim((string) $search);
            if (!in_array($key, $validParameters, true)) {
                $fail('validation.no_filter_instructions')->translate(['object' => $shortClass]);

                return;
            }
            if (strlen($search) > 50) {
                $fail('validation.no_filter_instructions')->translate(['object' => $shortClass]);

                return;
            }
        }
    }
}
