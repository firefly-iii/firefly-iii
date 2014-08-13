<?php

/**
 * Class DefaultUserSeeder
 */
class DefaultUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();

        User::create(
            [
                'email'          => 's@nder.be',
                'password'       => 'sander',
                'reset'          => null,
                'remember_token' => null,
                'migrated'       => 0
            ]
        );

    }

} 