<?php
/**
 * TransactionCurrencySeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
        TransactionCurrency::create(['code' => 'AUD', 'name' => 'New Zealand dollar', 'symbol' => 'NZ$', 'decimal_places' => 2]);

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
