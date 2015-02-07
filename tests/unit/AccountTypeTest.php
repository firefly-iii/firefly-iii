<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class AccountTypeTest
 */
class AccountTypeTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    // tests
    public function testAccounts()
    {
        $account = f::create('Account');
        $this->assertCount(1, $account->accountType()->first()->accounts()->get());
    }

}
