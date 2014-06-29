<?php


class TransactionCurrencySeeder extends Seeder
{
    public function run()
    {
        DB::table('transaction_currencies')->delete();

        TransactionCurrency::create(
            ['code' => 'EUR']
        );
    }

} 