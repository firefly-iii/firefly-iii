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

declare(strict_types = 1);

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\Seeder;

/**
 * Class TransactionCurrencySeeder
 */
class TransactionCurrencySeeder extends Seeder
{
    public function run()
    {
        DB::table('transaction_currencies')->delete();

        TransactionCurrency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€']);
        TransactionCurrency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
        TransactionCurrency::create(['code' => 'HUF', 'name' => 'Hungarian forint', 'symbol' => 'Ft']);
        TransactionCurrency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);
        TransactionCurrency::create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£']);
    }

} 
