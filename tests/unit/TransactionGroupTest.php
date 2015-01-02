<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class TransactionGroupTest
 */
class TransactionGroupTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testUser()
    {
        $group = f::create('TransactionGroup');
        $this->assertEquals($group->user_id, $group->user->id);
    }
}
