<?php
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

        AccountType::create(['type' => AccountType::DEFAULT_ACCOUNT, 'editable' => true]);
        AccountType::create(['type' => AccountType::CASH, 'editable' => false]);
        AccountType::create(['type' => AccountType::ASSET, 'editable' => true]);
        AccountType::create(['type' => AccountType::EXPENSE, 'editable' => true]);
        AccountType::create(['type' => AccountType::REVENUE, 'editable' => true]);
        AccountType::create(['type' => AccountType::INITIAL_BALANCE, 'editable' => false]);
        AccountType::create(['type' => AccountType::BENEFICIARY, 'editable' => true]);
        AccountType::create(['type' => AccountType::IMPORT, 'editable' => false]);
    }


} 
