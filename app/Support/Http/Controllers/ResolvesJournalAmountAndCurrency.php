<?php

/**
 * ResolvesJournalAmountAndCurrency.php
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

namespace FireflyIII\Support\Http\Controllers;

use FireflyIII\Support\Facades\Steam;

trait ResolvesJournalAmountAndCurrency
{
    /**
     * Normalize journal currency metadata and positive amount, honoring primary currency conversion.
     */
    protected function resolveJournalAmountAndCurrency(array $journal, ?array $currency = null): array
    {
        $currency ??= $journal;

        $currencyId            = (int) ($journal['currency_id'] ?? $currency['currency_id']);
        $currencyName          = (string) ($journal['currency_name'] ?? $currency['currency_name']);
        $currencySymbol        = (string) ($journal['currency_symbol'] ?? $currency['currency_symbol']);
        $currencyCode          = (string) ($journal['currency_code'] ?? $currency['currency_code']);
        $currencyDecimalPlaces = (int) ($journal['currency_decimal_places'] ?? $currency['currency_decimal_places'] ?? 2);
        $amount                = (string) $journal['amount'];

        if (
            $this->convertToPrimary
            && null !== $this->primaryCurrency
            && $currencyId !== $this->primaryCurrency->id
        ) {
            $currencyId            = $this->primaryCurrency->id;
            $currencyName          = $this->primaryCurrency->name;
            $currencySymbol        = $this->primaryCurrency->symbol;
            $currencyCode          = $this->primaryCurrency->code;
            $currencyDecimalPlaces = $this->primaryCurrency->decimal_places;
            $amount                = (int) ($journal['foreign_currency_id'] ?? 0) === $this->primaryCurrency->id
                ? (string) ($journal['foreign_amount'] ?? '0')
                : (string) ($journal['pc_amount'] ?? '0')
            ;
        }

        return [
            'currency_id'             => $currencyId,
            'currency_name'           => $currencyName,
            'currency_symbol'         => $currencySymbol,
            'currency_code'           => $currencyCode,
            'currency_decimal_places' => $currencyDecimalPlaces,
            'amount'                  => Steam::positive($amount),
        ];
    }
}
