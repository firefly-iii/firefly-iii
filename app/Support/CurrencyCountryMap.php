<?php

/**
 * CurrencyCountryMap.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace FireflyIII\Support;

/**
 * Class CurrencyCountryMap
 *
 * ISO 4217 currency codes have no 1:1 relationship with countries (the euro alone
 * covers twenty-odd states), so this is not modelled in the database. This map only
 * supplies a single, representative country label for display purposes.
 */
class CurrencyCountryMap
{
    private const array MAP = [
        'EUR' => 'Eurozone',
        'USD' => 'United States',
        'GBP' => 'United Kingdom',
        'INR' => 'India',
        'JPY' => 'Japan',
        'CNY' => 'China',
        'KRW' => 'South Korea',
        'AUD' => 'Australia',
        'NZD' => 'New Zealand',
        'CAD' => 'Canada',
        'CHF' => 'Switzerland',
        'HKD' => 'Hong Kong',
        'SEK' => 'Sweden',
        'NOK' => 'Norway',
        'DKK' => 'Denmark',
        'ISK' => 'Iceland',
        'PLN' => 'Poland',
        'CZK' => 'Czech Republic',
        'HUF' => 'Hungary',
        'RON' => 'Romania',
        'HRK' => 'Croatia',
        'TRY' => 'Türkiye',
        'UAH' => 'Ukraine',
        'RUB' => 'Russia',
        'ILS' => 'Israel',
        'ZAR' => 'South Africa',
        'EGP' => 'Egypt',
        'MAD' => 'Morocco',
        'BRL' => 'Brazil',
        'MXN' => 'Mexico',
        'IDR' => 'Indonesia',
        'SGD' => 'Singapore',
        'THB' => 'Thailand',
        'MYR' => 'Malaysia',
        'PHP' => 'Philippines',
        'VND' => 'Vietnam',
        'PKR' => 'Pakistan',
        'BDT' => 'Bangladesh',
        'AED' => 'United Arab Emirates',
        'SAR' => 'Saudi Arabia',
        'ARS' => 'Argentina',
        'CLP' => 'Chile',
        'COP' => 'Colombia',
        'PEN' => 'Peru',
    ];

    public static function get(string $code): string
    {
        return self::MAP[strtoupper($code)] ?? '—';
    }
}
