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
    // source: https://www.xe.com/currencyconverter/
    'date'             => '2025-04-15',

    // all rates are from EUR to $currency:
    'rates'            => [
        // europa
        'EUR' => 1,
        'HUF' => 410.79798,
        'GBP' => 0.86003261,
        'UAH' => 46.867455,
        'PLN' => 4.2802098,
        'TRY' => 43.180054,
        'DKK' => 7.4591,
        'RON' => 7.4648336,

        // Americas
        'USD' => 1.1349044,
        'BRL' => 6.6458518,
        'CAD' => 1.575105,
        'MXN' => 22.805278,

        // Oceania currencies
        'IDR' => 19070.382,
        'AUD' => 1.787202,
        'NZD' => 1.9191078,

        // africa
        'EGP' => 57.874172,
        'MAD' => 10.549438,
        'ZAR' => 21.444356,

        // asia
        'JPY' => 162.47195,
        'RMB' => 8.2849977,
        'CNY' => 8.2849977,
        'RUB' => 93.34423,
        'INR' => 97.572815,

        // int
        'ILS' => 4.1801786,
        'CHF' => 0.92683126,
        'HRK' => 7.5345, // replaced by EUR

        'ISK' => 145.10532,
        'NOK' => 11.980824,
        'SEK' => 11.08809,
        'HKD' => 8.8046322,
        'CZK' => 25.092213,
    ],
];
