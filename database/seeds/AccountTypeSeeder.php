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

        AccountType::create(['type' => 'Default account', 'editable' => true]);
        AccountType::create(['type' => 'Cash account', 'editable' => false]);
        AccountType::create(['type' => 'Asset account', 'editable' => true]);
        AccountType::create(['type' => 'Expense account', 'editable' => true]);
        AccountType::create(['type' => 'Revenue account', 'editable' => true]);
        AccountType::create(['type' => 'Initial balance account', 'editable' => false]);
        AccountType::create(['type' => 'Beneficiary account', 'editable' => true]);
        AccountType::create(['type' => 'Import account', 'editable' => false]);
    }


} 
