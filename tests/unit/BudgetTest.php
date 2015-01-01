<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class AccountTypeTest
 */
class BudgetTest extends TestCase
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
        $budget = f::create('Budget');
        $this->assertInstanceOf('User', $budget->user);

    }
}