<?php


/*
 * IsValidSlackUrl.php
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

namespace FireflyIII\Rules\Admin;

use FireflyIII\Support\Validation\ValidatesAmountsTrait;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class IsValidSlackUrl implements ValidationRule
{
    use ValidatesAmountsTrait;

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $value = (string) $value;
        if ('' === $value) {
            return;
        }

        if (!str_starts_with($value, 'https://hooks.slack.com/services/')) {
            $fail('validation.active_url')->translate();
            $message = sprintf('IsValidSlackUrl: "%s" is not a slack URL.', substr($value, 0, 255));
            Log::debug($message);
            Log::channel('audit')->info($message);
        }
    }
}
