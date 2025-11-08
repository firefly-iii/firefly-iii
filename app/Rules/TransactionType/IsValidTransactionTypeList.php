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

namespace FireflyIII\Rules\TransactionType;

use Closure;
use FireflyIII\Support\Http\Api\TransactionFilter;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;

class IsValidTransactionTypeList implements ValidationRule
{
    use TransactionFilter;

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        // only check the type.
        $values = [];
        if (is_string($value)) {
            $values = explode(',', $value);
        }
        if (!is_array($values)) {
            $fail('validation.invalid_transaction_type_list')->translate();
        }
        $keys   = array_keys($this->transactionTypes);
        foreach ($values as $entry) {
            $entry = (string)$entry;
            if (!in_array($entry, $keys, true)) {
                $fail('validation.invalid_transaction_type_list')->translate();
            }
        }
    }
}
