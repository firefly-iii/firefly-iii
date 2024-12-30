<?php

/*
 * cer.php
 * Copyright (c) 2022 james@firefly-iii.org
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

return [
    'url'              => 'https://ff3exchangerates.z6.web.core.windows.net',
    'enabled'          => env('ENABLE_EXCHANGE_RATES', false),
    'download_enabled' => env('ENABLE_EXTERNAL_RATES', false),

    // if currencies are added, default rates must be added as well!
    // last exchange rate update: 2024-12-30
    // source: https://www.xe.com/currencyconverter/
    'date'             => '2024-12-30',

    // all rates are from EUR to $currency:
    'rates'            => [
        // europa
        'EUR' => 1,
        'HUF' => 410.79798,
        'GBP' => 0.82858703,
        'UAH' => 43.485934,
        'PLN' => 4.2708542,
        'TRY' => 36.804124,
        'DKK' => 7.4591,
        'RON' => 4.9768699,

        // Americas
        'USD' => 1.0430046,
        'BRL' => 6.4639113,
        'CAD' => 1.5006908,
        'MXN' => 21.249542,

        // Oceania currencies
        'IDR' => 16860.057,
        'AUD' => 1.6705648,
        'NZD' => 1.8436945,

        // africa
        'EGP' => 53.038174,
        'MAD' => 10.521629,
        'ZAR' => 19.460263,

        // asia
        'JPY' => 164.74767,
        'RMB' => 7.6138994,
        'CNY' => 7.6138994,
        'RUB' => 108.56771,
        'INR' => 89.157391,

        // int
        'ILS' => 3.8428028,
        'CHF' => 0.94044969,
        'HRK' => 7.5345, // replaced by EUR
    ],
];
