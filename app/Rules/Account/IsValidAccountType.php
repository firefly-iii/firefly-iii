<?php
/*
 * IsValidAccountType.php
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

namespace FireflyIII\Rules\Account;

use Closure;
use FireflyIII\Support\Http\Api\AccountFilter;
use Illuminate\Contracts\Validation\ValidationRule;

class IsValidAccountType implements ValidationRule
{
    use AccountFilter;

    /**
     * @inheritDoc
     */
    #[\Override] public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        // only check the type.
        if (array_key_exists('type', $value)) {
            $value    = $value['type'];
            if (!is_array($value)) {
                $value = [$value];
            }


            $filtered = [];
            $keys     = array_keys($this->types);
            /** @var mixed $entry */
            foreach ($value as $entry) {
                $entry = (string) $entry;
                if (!in_array($entry, $keys)) {
                    $fail('something');
                }
            }
        }
    }
}
