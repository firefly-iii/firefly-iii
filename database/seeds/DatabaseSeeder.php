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
        $this->call(AccountTypeSeeder::class);
        $this->call(TransactionCurrencySeeder::class);
        $this->call(TransactionTypeSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(TestDataSeeder::class);

    }
}
