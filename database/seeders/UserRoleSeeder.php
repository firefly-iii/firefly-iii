<?php

namespace Database\Seeders;

use FireflyIII\Models\UserRole;
use Illuminate\Database\Seeder;
use PDOEXception;

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
            UserRole::FULL,
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
