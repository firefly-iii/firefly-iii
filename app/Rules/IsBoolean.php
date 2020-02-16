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

use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsBoolean
 */
class IsBoolean implements Rule
{
    /**
     * Get the validation error message.
     * @codeCoverageIgnore
     * @return string
     */
    public function message(): string
    {
        return (string)trans('validation.boolean');
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
        if (is_bool($value)) {
            return true;
        }
        if (is_int($value) && 0 === $value) {
            return true;
        }
        if (is_int($value) && 1 === $value) {
            return true;
        }
        if (is_string($value) && in_array($value, ['0', '1', 'true', 'false', 'on', 'off', 'yes', 'no', 'y', 'n'])) {
            return true;
        }

        return false;
    }
}
