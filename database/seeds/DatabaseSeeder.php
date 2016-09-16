<?php

use Illuminate\Database\Seeder;

/**
 * Class DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call('AccountTypeSeeder');
        $this->call('TransactionCurrencySeeder');
        $this->call('TransactionTypeSeeder');
        $this->call('PermissionSeeder');
        $this->call('TestDataSeeder');

    }
}
