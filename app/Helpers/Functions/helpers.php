<?php

/*
 * helpers.php
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

use FireflyIII\Exceptions\FireflyException;

use function Safe\mb_ord;
use function Safe\preg_match;
use function Safe\preg_replace_callback;

if (!function_exists('env_default_when_empty')) {
    /**
     * @return null|mixed
     */
    function env_default_when_empty(mixed $value, bool|int|string|null $default = null): mixed
    {
        if (null === $value) {
            return $default;
        }
        if ('' === $value) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('string_is_equal')) {
    function string_is_equal(string $left, string $right): bool
    {
        return $left === $right;
    }
}

if (!function_exists('blade_escape_js')) {
    function blade_escape_js(string $string): string
    {
        // escape all non-alphanumeric characters
        // into their \x or \uHHHH representations
        if (0 === preg_match('//u', $string)) {
            throw new FireflyException('The string to escape is not a valid UTF-8 string.');
        }

        return preg_replace_callback(
            '#[^a-zA-Z0-9,\._]#Su',
            static function ($matches) {
                $char      = $matches[0];

                /*
                 * A few characters have short escape sequences in JSON and JavaScript.
                 * Escape sequences supported only by JavaScript, not JSON, are omitted.
                 * \" is also supported but omitted, because the resulting string is not HTML safe.
                 */
                $short     = match ($char) {
                    '\\'    => '\\\\',
                    '/'     => '\/',
                    "\x08"  => '\b',
                    "\x0C"  => '\f',
                    "\x0A"  => '\n',
                    "\x0D"  => '\r',
                    "\x09"  => '\t',
                    default => false
                };

                if ($short) {
                    return $short;
                }

                $codepoint = mb_ord($char, 'UTF-8');
                if (0x10_000 > $codepoint) {
                    return \sprintf('\u%04X', $codepoint);
                }

                // Split characters outside the BMP into surrogate pairs
                // https://tools.ietf.org/html/rfc2781.html#section-2.1
                $u         = $codepoint - 0x10_000;
                $high      = 0xD800 | ($u >> 10);
                $low       = 0xDC00 | ($u & 0x3FF);

                return \sprintf('\u%04X\u%04X', $high, $low);
            },
            $string
        );
    }
}
