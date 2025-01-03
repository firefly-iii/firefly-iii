<?php

/*
 * IsFilterValueIn.php
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

use Illuminate\Contracts\Validation\ValidationRule;

class IsFilterValueIn implements ValidationRule
{
    private string $key;
    private array  $values;

    public function __construct(string $key, array $values)
    {
        $this->key    = $key;
        $this->values = $values;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }
        if (!array_key_exists($this->key, $value)) {
            return;
        }
        $value = $value[$this->key] ?? null;

        if (!is_string($value) && null !== $value) {
            $fail('validation.filter_not_string')->translate(['filter' => $this->key]);
        }
        if (!in_array($value, $this->values, true)) {
            $fail('validation.filter_must_be_in')->translate(['filter' => $this->key, 'values' => implode(', ', $this->values)]);
        }
        // $fail('validation.filter_not_string')->translate(['filter' => $this->key]);
    }
}
