<?php

/**
 * TransactionCurrencySeeder.php
 * Copyright (c) 2019 james@firefly-iii.org.
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

namespace Database\Seeders;

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\Seeder;
use PDOException;

/**
 * Class TransactionCurrencySeeder.
 */
class TransactionCurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies   = [];
        // european currencies
        $currencies[] = ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'enabled' => 1];
        $currencies[] = ['code' => 'HUF', 'name' => 'Hungarian forint', 'symbol' => 'Ft', 'decimal_places' => 2];
        $currencies[] = ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2];
        $currencies[] = ['code' => 'UAH', 'name' => 'Ukrainian hryvnia', 'symbol' => '₴', 'decimal_places' => 2];
        $currencies[] = ['code' => 'PLN', 'name' => 'Polish złoty', 'symbol' => 'zł', 'decimal_places' => 2];
        $currencies[] = ['code' => 'TRY', 'name' => 'Turkish lira', 'symbol' => '₺', 'decimal_places' => 2];
        $currencies[] = ['code' => 'DKK', 'name' => 'Dansk krone', 'symbol' => 'kr.', 'decimal_places' => 2];
        $currencies[] = ['code' => 'ISK', 'name' => 'Íslensk króna', 'symbol' => 'kr.', 'decimal_places' => 2];
        $currencies[] = ['code' => 'NOK', 'name' => 'Norsk krone', 'symbol' => 'kr.', 'decimal_places' => 2];
        $currencies[] = ['code' => 'SEK', 'name' => 'Svensk krona', 'symbol' => 'kr.', 'decimal_places' => 2];
        $currencies[] = ['code' => 'RON', 'name' => 'Romanian leu', 'symbol' => 'lei', 'decimal_places' => 2];

        // american currencies
        $currencies[] = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'BRL', 'name' => 'Brazilian real', 'symbol' => 'R$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'CAD', 'name' => 'Canadian dollar', 'symbol' => 'C$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'MXN', 'name' => 'Mexican peso', 'symbol' => 'MX$', 'decimal_places' => 2];

        // oceanian currencies
        $currencies[] = ['code' => 'IDR', 'name' => 'Indonesian rupiah', 'symbol' => 'Rp', 'decimal_places' => 2];
        $currencies[] = ['code' => 'AUD', 'name' => 'Australian dollar', 'symbol' => 'A$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'NZD', 'name' => 'New Zealand dollar', 'symbol' => 'NZ$', 'decimal_places' => 2];

        // african currencies
        $currencies[] = ['code' => 'EGP', 'name' => 'Egyptian pound', 'symbol' => 'E£', 'decimal_places' => 2];
        $currencies[] = ['code' => 'MAD', 'name' => 'Moroccan dirham', 'symbol' => 'DH', 'decimal_places' => 2];
        $currencies[] = ['code' => 'ZAR', 'name' => 'South African rand', 'symbol' => 'R', 'decimal_places' => 2];

        // asian currencies
        $currencies[] = ['code' => 'JPY', 'name' => 'Japanese yen', 'symbol' => '¥', 'decimal_places' => 0];
        // $currencies[] = ['code' => 'CNY', 'name' => 'Chinese yuan', 'symbol' => '¥', 'decimal_places' => 2];
        $currencies[] = ['code' => 'RMB', 'name' => 'Chinese yuan', 'symbol' => '¥', 'decimal_places' => 2];
        $currencies[] = ['code' => 'RUB', 'name' => 'Russian ruble', 'symbol' => '₽', 'decimal_places' => 2];
        $currencies[] = ['code' => 'INR', 'name' => 'Indian rupee', 'symbol' => '₹', 'decimal_places' => 2];

        // ALL NEW CURRENCIES BELOW THIS LINE
        $currencies[] = ['code' => 'ILS', 'name' => 'Israeli new shekel', 'symbol' => '₪', 'decimal_places' => 2];
        $currencies[] = ['code' => 'CHF', 'name' => 'Swiss franc', 'symbol' => 'CHF', 'decimal_places' => 2];
        $currencies[] = ['code' => 'HRK', 'name' => 'Croatian kuna', 'symbol' => 'kn', 'decimal_places' => 2];
        $currencies[] = ['code' => 'HKD', 'name' => 'Hong Kong dollar', 'symbol' => 'HK$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'CHF', 'name' => 'Swiss franc', 'symbol' => 'CHF', 'decimal_places' => 2];
        $currencies[] = ['code' => 'NOK', 'name' => 'Norwegian krone', 'symbol' => 'kr.', 'decimal_places' => 2];
        $currencies[] = ['code' => 'CZK', 'name' => 'Czech koruna', 'symbol' => 'Kč', 'decimal_places' => 2];

        foreach ($currencies as $currency) {
            if (null === TransactionCurrency::where('code', $currency['code'])->first()) {
                try {
                    TransactionCurrency::create($currency);
                } catch (PDOException $e) {
                    // @ignoreException
                }
            }
        }
    }
}
