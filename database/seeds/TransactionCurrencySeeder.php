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
        TransactionCurrency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'HUF', 'name' => 'Hungarian forint', 'symbol' => 'Ft', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'IDR', 'name' => 'Indonesian rupiah', 'symbol' => 'Rp', 'decimal_places' => 2]);
        TransactionCurrency::create(['code' => 'XBT', 'name' => 'Bitcoin', 'symbol' => 'B', 'decimal_places' => 8]);
        TransactionCurrency::create(['code' => 'JPY', 'name' => 'Japanese yen', 'symbol' => '¥', 'decimal_places' => 2]);
    }

} 
