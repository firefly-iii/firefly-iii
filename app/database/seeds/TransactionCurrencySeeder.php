<?php


/**
 * Class TransactionCurrencySeeder
 */
class TransactionCurrencySeeder extends Seeder
{
    public function run()
    {
        DB::table('transaction_currencies')->delete();

        TransactionCurrency::create(['code' => 'EUR','name' => 'Euro','symbol' => '&#8364;']);
        TransactionCurrency::create(['code' => 'USD','name' => 'US Dollar','symbol' => '$']);
    }

} 