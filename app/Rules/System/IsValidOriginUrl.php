<?php

/*
 * IsValidOriginUrl.php
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

declare(strict_types=1);

namespace FireflyIII\Rules\System;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Safe\Exceptions\UrlException;

use function Safe\parse_url;

class IsValidOriginUrl implements ValidationRule
{
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!auth()->check()) {
            $fail('validation.no_auth_present')->translate();

            return;
        }
        $value = (string) $value;
        if (str_contains($value, '%2F')) {
            $value = urldecode($value);
        }
        if ('' === $value) {
            // string can be empty.
            return;
        }

        try {
            $parts = parse_url($value);
        } catch (UrlException) {
            $fail('validation.bad_url_parts')->translate();

            return;
        }
        if (!array_key_exists('path', $parts) || array_key_exists('scheme', $parts) || array_key_exists('host', $parts)) {
            $fail('validation.bad_url_parts')->translate();

            return;
        }
        if (!str_starts_with($parts['path'], '/')) {
            $fail('validation.bad_url_parts')->translate();

            // return;
        }
    }
}
