<?php

/*
 * ValidatesAmountsTrait.php
 * Copyright (c) 2024 james@firefly-iii.org
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

namespace FireflyIII\Support\Validation;

trait ValidatesAmountsTrait
{
    // 19-09-2020: my wedding day
    protected const string BIG_AMOUNT = '10019092020';

    final protected function emptyString(string $value): bool
    {
        return '' === $value;
    }

    final protected function isValidNumber(string $value): bool
    {
        return is_numeric($value);
    }

    final protected function lessOrEqualToZero(string $value): bool
    {
        return -1 === bccomp($value, '0') || 0 === bccomp($value, '0');
    }

    final protected function lessThanLots(string $value): bool
    {
        $amount = bcmul('-1', self::BIG_AMOUNT);

        return -1 === bccomp($value, $amount) || 0 === bccomp($value, $amount);
    }

    final protected function moreThanLots(string $value): bool
    {
        return 1 === bccomp($value, self::BIG_AMOUNT) || 0 === bccomp($value, self::BIG_AMOUNT);
    }

    final protected function scientificNumber(string $value): bool
    {
        return str_contains(strtoupper($value), 'E');
    }

    final protected function zeroOrMore(string $value): bool
    {
        return 1 === bccomp($value, '0') || 0 === bccomp($value, '0');
    }
}
