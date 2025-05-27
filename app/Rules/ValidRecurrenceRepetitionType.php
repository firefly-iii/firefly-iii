<?php

/**
 * ValidRecurrenceRepetitionType.php
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

use Illuminate\Contracts\Validation\ValidationRule;
use Closure;

/**
 * Class ValidRecurrenceRepetitionType
 */
class ValidRecurrenceRepetitionType implements ValidationRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;
        if ('daily' === $value) {
            return;
        }
        // monthly,17
        // ndom,3,7
        if (in_array(substr($value, 0, 6), ['yearly', 'weekly'], true)) {
            return;
        }
        if (str_starts_with($value, 'monthly')) {
            return;
        }
        if (str_starts_with($value, 'ndom')) {
            return;
        }

        $fail('validation.valid_recurrence_rep_type')->translate();
    }
}
