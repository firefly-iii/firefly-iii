<?php
/**
 * ConvertsDataTypes.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace FireflyIII\Support\Request;

use Carbon\Carbon;
use Exception;
use Log;

/**
 * Trait ConvertsDataTypes
 */
trait ConvertsDataTypes
{

    /**
     * Return date or NULL.
     *
     * @param string $field
     *
     * @return Carbon|null
     */
    protected function date(string $field): ?Carbon
    {
        $result = null;
        try {
            $result = $this->get($field) ? new Carbon($this->get($field)) : null;
        } catch (Exception $e) {
            Log::debug(sprintf('Exception when parsing date. Not interesting: %s', $e->getMessage()));
        }

        return $result;
    }

    /**
     * Return integer value.
     *
     * @param string $field
     *
     * @return int
     */
    protected function integer(string $field): int
    {
        return (int) $this->get($field);
    }


    /**
     * Return floating value.
     *
     * @param string $field
     *
     * @return float|null
     */
    protected function float(string $field): ?float
    {
        $res = $this->get($field);
        if (null === $res) {
            return null;
        }

        return (float) $res;
    }


    /**
     * Parse and clean a string, but keep the newlines.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    protected function nlStringFromValue(?string $string): ?string
    {
        if (null === $string) {
            return null;
        }
        $result = app('steam')->nlCleanString($string);

        return '' === $result ? null : $result;

    }

    /**
     * @param $array
     *
     * @return array|null
     */
    protected function arrayFromValue($array): ?array
    {
        if (is_array($array)) {
            return $array;
        }
        if (null === $array) {
            return null;
        }
        if (is_string($array)) {
            return explode(',', $array);
        }

        return null;
    }



    /**
     * @param string|null $string
     *
     * @return Carbon|null
     */
    protected function dateFromValue(?string $string): ?Carbon
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }
        try {
            $carbon = new Carbon($string);
        } catch (Exception $e) {
            Log::debug(sprintf('Invalid date: %s: %s', $string, $e->getMessage()));

            return null;
        }

        return $carbon;
    }

    /**
     * Parse and clean a string.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    protected function stringFromValue(?string $string): ?string
    {
        if (null === $string) {
            return null;
        }
        $result = app('steam')->cleanString($string);

        return '' === $result ? null : $result;
    }


    /**
     * Parse to integer
     *
     * @param string|null $string
     *
     * @return int|null
     */
    protected function integerFromValue(?string $string): ?int
    {
        if (null === $string) {
            return null;
        }
        if ('' === $string) {
            return null;
        }

        return (int) $string;
    }

    /**
     * Return string value, but keep newlines.
     *
     * @param string $field
     *
     * @return string
     */
    protected function nlString(string $field): string
    {
        return app('steam')->nlCleanString((string) ($this->get($field) ?? ''));
    }

    /**
     * Return integer value, or NULL when it's not set.
     *
     * @param string $field
     *
     * @return int|null
     */
    protected function nullableInteger(string $field): ?int
    {
        if (!$this->has($field)) {
            return null;
        }

        $value = (string) $this->get($field);
        if ('' === $value) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Return string value, but keep newlines, or NULL if empty.
     *
     * @param string $field
     *
     * @return string
     */
    protected function nullableNlString(string $field): ?string
    {
        if (!$this->has($field)) {
            return null;
        }

        return app('steam')->nlCleanString((string) ($this->get($field) ?? ''));
    }


    /**
     * @param string $value
     *
     * @return bool
     */
    protected function convertBoolean(?string $value): bool
    {
        if (null === $value) {
            return false;
        }
        if ('true' === $value) {
            return true;
        }
        if ('yes' === $value) {
            return true;
        }
        if (1 === $value) {
            return true;
        }
        if ('1' === $value) {
            return true;
        }
        if (true === $value) {
            return true;
        }

        return false;
    }

    /**
     * Return string value, or NULL if empty.
     *
     * @param string $field
     *
     * @return string|null
     */
    protected function nullableString(string $field): ?string
    {
        if (!$this->has($field)) {
            return null;
        }
        $res = trim(app('steam')->cleanString((string) ($this->get($field) ?? '')));
        if ('' === $res) {
            return null;
        }

        return $res;
    }

    /**
     * Return string value.
     *
     * @param string $field
     *
     * @return string
     */
    protected function string(string $field): string
    {
        return app('steam')->cleanString((string) ($this->get($field) ?? ''));
    }
}
