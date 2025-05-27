<?php

/**
 * PermissionSeeder.php
 * Copyright (c) 2019 james@firefly-iii.org.
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

use FireflyIII\Models\Role;
use Illuminate\Database\Seeder;
use PDOException;

/**
 * Class PermissionSeeder.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'owner',
                'display_name' => 'Site Owner',
                'description'  => 'User runs this instance of FF3',
            ],
            [
                'name'         => 'demo',
                'display_name' => 'Demo User',
                'description'  => 'User is a demo user',
            ],
        ];
        foreach ($roles as $role) {
            if (null === Role::where('name', $role['name'])->first()) {
                try {
                    Role::create($role);
                } catch (PDOException $e) {
                    // @ignoreException
                }
            }
        }
    }
}
