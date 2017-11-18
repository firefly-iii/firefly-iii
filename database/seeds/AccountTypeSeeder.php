<?php
/**
 * AccountTypeSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

use FireflyIII\Models\AccountType;
use Illuminate\Database\Seeder;

/**
 * Class AccountTypeSeeder
 */
class AccountTypeSeeder extends Seeder
{
    public function run()
    {
        AccountType::create(['type' => AccountType::DEFAULT]);
        AccountType::create(['type' => AccountType::CASH]);
        AccountType::create(['type' => AccountType::ASSET]);
        AccountType::create(['type' => AccountType::EXPENSE]);
        AccountType::create(['type' => AccountType::REVENUE]);
        AccountType::create(['type' => AccountType::INITIAL_BALANCE]);
        AccountType::create(['type' => AccountType::BENEFICIARY]);
        AccountType::create(['type' => AccountType::IMPORT]);
        AccountType::create(['type' => AccountType::LOAN]);
        AccountType::create(['type' => AccountType::RECONCILIATION]);
    }
}
