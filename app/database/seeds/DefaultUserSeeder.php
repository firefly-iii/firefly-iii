<?php

/**
 * Created by PhpStorm.
 * User: sander
 * Date: 03/07/14
 * Time: 21:06
 */
class DefaultUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->delete();

        User::create(
            [
                'email'          => 's@nder.be',
                'password'       => Hash::make('sander'),
                'verification'   => null,
                'reset'          => null,
                'remember_token' => null,
                'migrated'       => false
            ]
        );
    }

} 