<?php
/*
 * default_cer.php
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


return [
    // if currencies are added, default rates must be added as well!
    // last exchange rate update: 6-6-2022
    // source: https://www.xe.com/currencyconverter/
    'date'  => '2022-06-06',
    'rates' => [
        // europa
        ['EUR', 'HUF', 387.9629],
        ['EUR', 'GBP', 0.85420754],
        ['EUR', 'UAH', 31.659752],
        ['EUR', 'PLN', 4.581788],
        ['EUR', 'TRY', 17.801397],
        ['EUR', 'DKK', 7.4389753],

        // Americas
        ['EUR', 'USD', 1.0722281],
        ['EUR', 'BRL', 5.0973173],
        ['EUR', 'CAD', 1.3459969],
        ['EUR', 'MXN', 20.899824],

        // Oceania currencies
        ['EUR', 'IDR', 15466.299],
        ['EUR', 'AUD', 1.4838549],
        ['EUR', 'NZD', 1.6425829],

        // africa
        ['EUR', 'EGP', 19.99735],
        ['EUR', 'MAD', 10.573307],
        ['EUR', 'ZAR', 16.413167],

        // asia
        ['EUR', 'JPY', 140.15257],
        ['EUR', 'RMB', 7.1194265],
        ['EUR', 'RUB', 66.000895],
        ['EUR', 'INR', 83.220481],

        // int
        ['EUR', 'XBT', 0, 00003417],
        ['EUR', 'BCH', 0.00573987],
        ['EUR', 'ETH', 0, 00056204],

        ['EUR', 'ILS', 3.5712508],
        ['EUR', 'CHF', 1.0323891],
        ['EUR', 'HRK', 7.5220845],
    ],
];
