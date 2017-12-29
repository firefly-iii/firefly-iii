<?php
/**
 * AccountTypeSeeder.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
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
