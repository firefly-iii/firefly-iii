<?php


/**
 * Class AccountTypeSeeder
 */
class AccountTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('account_types')->delete();

        AccountType::create(
            ['type' => 'Default account','editable' => true]
        );
        AccountType::create(
            ['type' => 'Cash account','editable' => false]
        );
        AccountType::create(
            ['type' => 'Initial balance account','editable' => false]
        );
        AccountType::create(
            ['type' => 'Beneficiary account','editable' => true]
        );
    }


} 