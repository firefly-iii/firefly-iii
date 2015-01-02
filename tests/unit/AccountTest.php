<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class AccountTest
 */
class AccountTest extends TestCase
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

    public function testAccountMeta()
    {
        $account = f::create('Account');
        $newMeta = $account->updateMeta('field', 'value');
        $this->assertInstanceOf('AccountMeta', $newMeta);
        $secondMeta = $account->updateMeta('field', 'newValue');
        $this->assertEquals($newMeta->id, $secondMeta->id);
        $this->assertEquals($newMeta->data, 'value');
        $this->assertEquals($secondMeta->data, 'newValue');
    }

    public function testAccountUser()
    {
        $account = f::create('Account');
        $this->assertInstanceOf('Account', $account);
        $this->assertInstanceOf('User', $account->user);
    }

}
