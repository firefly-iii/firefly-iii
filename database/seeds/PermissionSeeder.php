<?php
/**
 * PermissionSeeder.php
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

use FireflyIII\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Class PermissionSeeder
 */
class PermissionSeeder extends Seeder
{
    public function run()
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
            try {
                Role::create($role);
            } catch (PDOException $e) {
                Log::warning(sprintf('Could not create role "%s". It might exist already.', $role['display_name']));
            }
        }

    }
}
