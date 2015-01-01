<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class ReminderTest
 */
class ReminderTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testDateIs()
    {
        $reminder = f::create('Reminder');
        $start    = clone $reminder->startdate;
        $end      = clone $reminder->enddate;
        $this->assertCount(1, Reminder::dateIs($start, $end)->get());

    }

    public function testUser()
    {
        $user              = f::create('User');
        $reminder          = f::create('Reminder');
        $reminder->user_id = $user->id;
        $reminder->save();

        $this->assertEquals($reminder->user->id, $user->id);
    }
}