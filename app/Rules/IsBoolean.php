<?php

/**
 * IsBoolean.php
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
 * Class IsBoolean
 */
class IsBoolean implements ValidationRule
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_bool($value)) {
            return;
        }
        if (0 === $value) {
            return;
        }
        if (1 === $value) {
            return;
        }
        if (in_array($value, ['0', '1', 'true', 'false', 'on', 'off', 'yes', 'no', 'y', 'n'], true)) {
            return;
        }
        $fail('validation.boolean')->translate();
    }
}
