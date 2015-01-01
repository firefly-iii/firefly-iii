<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class PiggyBankTest
 */
class PiggyBankTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testPiggyBankReminders()
    {
        $reminder  = f::create('Reminder');
        $piggyBank = f::create('PiggyBank');
        $piggyBank->reminders()->save($reminder);
        $this->assertCount(1, $piggyBank->reminders()->get());
    }
}