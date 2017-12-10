<?php
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
        $owner               = new Role;
        $owner->name         = 'owner';
        $owner->display_name = 'Site Owner';
        $owner->description  = 'User runs this instance of FF3'; // optional
        $owner->save();

        $demo               = new Role;
        $demo->name         = 'demo';
        $demo->display_name = 'Demo User';
        $demo->description  = 'User is a demo user';
        $demo->save();
    }
}
