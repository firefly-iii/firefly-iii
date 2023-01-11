<?php

/*
 * UserRoleSeeder.php
 * Copyright (c) 2021 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Database\Seeders;

use FireflyIII\Models\UserRole;
use Illuminate\Database\Seeder;
use PDOException;

/**
 * Class UserRoleSeeder
 */
class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            UserRole::READ_ONLY,
            UserRole::CHANGE_TRANSACTIONS,
            UserRole::CHANGE_RULES,
            UserRole::CHANGE_PIGGY_BANKS,
            UserRole::CHANGE_REPETITIONS,
            UserRole::VIEW_REPORTS,
            UserRole::MANAGE_WEBHOOKS,
            UserRole::MANAGE_CURRENCIES,
            UserRole::FULL,
            UserRole::OWNER,
        ];

        /** @var string $role */
        foreach ($roles as $role) {
            try {
                UserRole::create(['title' => $role]);
            } catch (PDOException $e) {
                // @ignoreException
            }
        }
    }
}
