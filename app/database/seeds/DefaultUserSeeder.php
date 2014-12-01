<?php

/**
 * Class DefaultUserSeeder
 */
class DefaultUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();
        if (App::environment() == 'homestead') {

            User::create(
                ['email' => 'thegrumpydictator@gmail.com', 'password' => 'james', 'reset' => null, 'remember_token' => null, 'migrated' => 0]
            );
            User::create(
                ['email' => 'acceptance@example.com', 'password' => 'acceptance', 'reset' => null, 'remember_token' => null, 'migrated' => 0]
            );
        }

    }

} 