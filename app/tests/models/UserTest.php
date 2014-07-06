<?php

class UserTest extends TestCase
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
    }

    /**
     * Username is required
     */
    public function testUsernameIsRequired()
    {
        // Create a new User
        $user = new User;
        $user->migrated = 0;
        $user->password = Str::random(60);

        // User should not save
        $this->assertFalse($user->isValid());

        // Save the errors
        $errors = $user->validator->messages()->all();
//        // There should be 1 error
        $this->assertCount(1, $errors);

//        // The username error should be set
        $this->assertEquals($errors[0], "The email field is required.");
    }

    /**
     * Test accounts
     */
    public function testUserAccounts()
    {
        // Create a new User
        $user = new User;
        $user->email = 'bla';
        $user->password = 'bla';
        $user->migrated = 0;
        $user->save();

        // account type:
        $at = new AccountType;
        $at->description = 'Bla';
        $at->save();


        $account = new Account;
        $account->name = 'bla';
        $account->active = 1;
        $account->accountType()->associate($at);
        $account->user()->associate($user);

        $account->save();

        $this->assertCount(1,$user->accounts()->get());

    }

} 