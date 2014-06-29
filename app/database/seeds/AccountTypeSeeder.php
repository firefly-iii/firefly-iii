<?php


class AccountTypeSeeder extends Seeder
{
    public function run()
    {
        DB::table('account_types')->delete();

        AccountType::create(
            ['description' => 'Default account']
        );
        AccountType::create(
            ['description' => 'Cash account']
        );
        AccountType::create(
            ['description' => 'Initial balance account']
        );
        AccountType::create(
            ['description' => 'Beneficiary account']
        );
    }


} 