<?php
/**
 * DatabaseSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types = 1);

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
