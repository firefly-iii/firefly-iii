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
    'enabled'          => true,
    'download_enabled' => env('ENABLE_EXTERNAL_RATES', false),

    // if currencies are added, default rates must be added as well!
    // last exchange rate update: 6-6-2022
    // source: https://www.xe.com/currencyconverter/
    'date'             => '2022-06-06',

    // all rates are from EUR to $currency:
    'rates'            => [
        // europa
        'EUR' => 1,
        'HUF' => 387.9629,
        'GBP' => 0.85420754,
        'UAH' => 31.659752,
        'PLN' => 4.581788,
        'TRY' => 17.801397,
        'DKK' => 7.4389753,

        // Americas
        'USD' => 1.0722281,
        'BRL' => 5.0973173,
        'CAD' => 1.3459969,
        'MXN' => 20.899824,

        // Oceania currencies
        'IDR' => 15466.299,
        'AUD' => 1.4838549,
        'NZD' => 1.6425829,

        // africa
        'EGP' => 19.99735,
        'MAD' => 10.573307,
        'ZAR' => 16.413167,

        // asia
        'JPY' => 140.15257,
        'RMB' => 7.1194265,
        'CNY' => 1,
        'RUB' => 66.000895,
        'INR' => 83.220481,

        // int
        'ILS' => 3.5712508,
        'CHF' => 1.0323891,
        'HRK' => 7.5220845,
    ],
];
