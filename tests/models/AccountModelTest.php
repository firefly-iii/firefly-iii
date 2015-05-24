<?php

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
        $this->markTestIncomplete();
    }

    /**
     * @covers FireflyIII\Models\Account::firstOrNullEncrypted
     */
    public function testFirstOrNullEncrypted()
    {
        $this->markTestIncomplete();
    }

}