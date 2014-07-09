<?php

use Zizaco\FactoryMuff\Facade\FactoryMuff;

class AllModelsTest extends TestCase
{
    /**
     * Default preparation for each test
     */
    public function setUp()
    {
        parent::setUp();

        $this->prepareForTests();
    }

    /**
     * Migrate the database
     */
    private function prepareForTests()
    {
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }


    /**
     * User tests
     */
    public function testUser()
    {
        $user = FactoryMuff::create('User');
        $pref = FactoryMuff::create('Preference');
        $account = FactoryMuff::create('Account');

        $account->user()->associate($user);
        $pref->user()->associate($user);

        $this->assertEquals($account->user_id, $user->id);
        $this->assertEquals($pref->user_id,$user->id);


        $this->assertTrue(true);

    }

    /**
     * Account tests
     */
    public function testUserAccounts()
    {
        $this->assertTrue(true);

    }
} 