<?php

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

    }

}