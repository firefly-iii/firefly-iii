<?php

use FireflyIII\Models\Account;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class AccountModelTest
 */
class AccountModelTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers FireflyIII\Models\Account::firstOrCreateEncrypted
     */
    public function testFirstOrCreateEncrypted()
    {
        // create account:
        $account = FactoryMuffin::create('FireflyIII\Models\Account');


        // search for account with the same properties:
        $search = [
            'name'            => $account->name,
            'account_type_id' => $account->account_type_id,
            'user_id'         => $account->user_id
        ];

        $result = Account::firstOrCreateEncrypted($search);

        // should be the same account:

        $this->assertEquals($account->id, $result->id);

    }

    /**
     * @covers FireflyIII\Models\Account::firstOrCreateEncrypted
     */
    public function testFirstOrCreateEncryptedNew()
    {
        // create account:
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        FactoryMuffin::create('FireflyIII\User');

        // search for account with the same properties:
        $search = [
            'name'            => 'Some new account',
            'account_type_id' => $account->account_type_id,
            'user_id'         => $account->user_id,
            'active'          => 1,
        ];

        $result = Account::firstOrCreateEncrypted($search);

        // should not be the same account:

        $this->assertNotEquals($account->id, $result->id);


    }

    /**
     * @covers FireflyIII\Models\Account::firstOrNullEncrypted
     */
    public function testFirstOrNullEncrypted()
    {
        // create account:
        $account = FactoryMuffin::create('FireflyIII\Models\Account');


        // search for account with the same properties:
        $search = [
            'name'            => $account->name,
            'account_type_id' => $account->account_type_id,
            'user_id'         => $account->user_id
        ];

        $result = Account::firstOrNullEncrypted($search);

        // should be the same account:

        $this->assertEquals($account->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\Account::firstOrNullEncrypted
     */
    public function testFirstOrNullEncryptedNew()
    {
        // create account:
        $account = FactoryMuffin::create('FireflyIII\Models\Account');
        FactoryMuffin::create('FireflyIII\User');

        // search for account with the same properties:
        $search = [
            'name'            => 'Some new account',
            'account_type_id' => $account->account_type_id,
            'user_id'         => $account->user_id,
            'active'          => 1,
        ];

        $result = Account::firstOrNullEncrypted($search);

        // should not be the same account:

        $this->assertNull($result);


    }

}
