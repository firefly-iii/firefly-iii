<?php
/**
 * TransactionCurrencySeeder.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\Seeder;

/**
 * Class TransactionCurrencySeeder
 */
class TransactionCurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = [];
        // european currencies
        $currencies[] = ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2];
        $currencies[] = ['code' => 'HUF', 'name' => 'Hungarian forint', 'symbol' => 'Ft', 'decimal_places' => 2];
        $currencies[] = ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2];

        // american currencies
        $currencies[] = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'BRL', 'name' => 'Brazilian real', 'symbol' => 'R$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'CAD', 'name' => 'Canadian dollar', 'symbol' => 'C$', 'decimal_places' => 2];

        // oceanian currencies
        $currencies[] = ['code' => 'IDR', 'name' => 'Indonesian rupiah', 'symbol' => 'Rp', 'decimal_places' => 2];
        $currencies[] = ['code' => 'AUD', 'name' => 'Australian dollar', 'symbol' => 'A$', 'decimal_places' => 2];
        $currencies[] = ['code' => 'NZD', 'name' => 'New Zealand dollar', 'symbol' => 'NZ$', 'decimal_places' => 2];

        // african currencies
        $currencies[] = ['code' => 'EGP', 'name' => 'Egyptian pound', 'symbol' => 'E£', 'decimal_places' => 2];
        $currencies[] = ['code' => 'MAD', 'name' => 'Moroccan dirham', 'symbol' => 'DH', 'decimal_places' => 2];
        $currencies[] = ['code' => 'ZAR', 'name' => 'South African rand', 'symbol' => 'R', 'decimal_places' => 2];

        // asian currencies
        $currencies[] = ['code' => 'JPY', 'name' => 'Japanese yen', 'symbol' => '¥', 'decimal_places' => 2];
        $currencies[] = ['code' => 'RMB', 'name' => 'Chinese yuan', 'symbol' => '元', 'decimal_places' => 2];
        $currencies[] = ['code' => 'RUB', 'name' => 'Russian ruble ', 'symbol' => '₽', 'decimal_places' => 2];

        // international currencies
        $currencies[] = ['code' => 'XBT', 'name' => 'Bitcoin', 'symbol' => '₿', 'decimal_places' => 8];
        $currencies[] = ['code' => 'BCH', 'name' => 'Bitcoin cash', 'symbol' => '₿C', 'decimal_places' => 8];
        $currencies[] = ['code' => 'ETH', 'name' => 'Ethereum', 'symbol' => 'Ξ', 'decimal_places' => 12];

        foreach ($currencies as $currency) {
            try {
                TransactionCurrency::create($currency);
            } catch (PDOException $e) {
                Log::warning(sprintf('Could not create transaction currency "%s". It might exist already.', $currency['code']));
            }
        }
    }
}
