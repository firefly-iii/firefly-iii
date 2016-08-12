<?php
declare(strict_types = 1);

use FireflyIII\Models\AccountType;
use Illuminate\Database\Seeder;

/**
 * Class AccountTypeSeeder
 */
class AccountTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('account_types')->delete();

        AccountType::create(['type' => 'Default account']);
        AccountType::create(['type' => 'Cash account']);
        AccountType::create(['type' => 'Asset account']);
        AccountType::create(['type' => 'Expense account']);
        AccountType::create(['type' => 'Revenue account']);
        AccountType::create(['type' => 'Initial balance account']);
        AccountType::create(['type' => 'Beneficiary account']);
        AccountType::create(['type' => 'Import account']);
    }


} 
