<?php

class DatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        $this->call('AccountTypeSeeder');
        $this->call('TransactionCurrencySeeder');
        $this->call('TransactionTypeSeeder');
        $this->call('DefaultUserSeeder');
    }

}
