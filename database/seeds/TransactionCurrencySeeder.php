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
        // european currencies
        TransactionCurrency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'HUF', 'name' => 'Hungarian forint', 'symbol' => 'Ft', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2]);

        // american currencies
        TransactionCurrency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'BRL', 'name' => 'Brazilian real', 'symbol' => 'R$', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'CAD', 'name' => 'Canadian dollar', 'symbol' => 'C$', 'decimal_places' => 2]);

        // oceanian currencies
        TransactionCurrency::create(['code' => 'IDR', 'name' => 'Indonesian rupiah', 'symbol' => 'Rp', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'AUD', 'name' => 'Australian dollar', 'symbol' => 'A$', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'NZD', 'name' => 'New Zealand dollar', 'symbol' => 'NZ$', 'decimal_places' => 2]);

        // african currencies
        TransactionCurrency::create(['code' => 'EGP', 'name' => 'Egyptian pound', 'symbol' => 'E£', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'MAD', 'name' => 'Moroccan dirham', 'symbol' => 'DH', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'ZAR', 'name' => 'South African rand', 'symbol' => 'R', 'decimal_places' => 2]);

        // asian currencies
        TransactionCurrency::create(['code' => 'JPY', 'name' => 'Japanese yen', 'symbol' => '¥', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'RMB', 'name' => 'Chinese yuan', 'symbol' => '元', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'RUB', 'name' => 'Russian ruble ', 'symbol' => '₽', 'decimal_places' => 2]);

        // international currencies
        TransactionCurrency::create(['code' => 'XBT', 'name' => 'Bitcoin', 'symbol' => '₿', 'decimal_places' => 8]);
        TransactionCurrency::create(['code' => 'BCH', 'name' => 'Bitcoin cash', 'symbol' => '₿C', 'decimal_places' => 8]);
        TransactionCurrency::create(['code' => 'ETH', 'name' => 'Ethereum', 'symbol' => 'Ξ', 'decimal_places' => 12]);
    }
}
