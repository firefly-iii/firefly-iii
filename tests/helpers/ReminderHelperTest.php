<?php

use Carbon\Carbon;
use FireflyIII\Helpers\Reminders\ReminderHelper;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ReminderHelperTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ReminderHelperTest extends TestCase
{


    /**
     * @var ReminderHelper
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        FactoryMuffin::create('FireflyIII\User');
        $this->object = new ReminderHelper;
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
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::createReminder
     */
    public function testCreateReminder()
    {
        $account               = FactoryMuffin::create('FireflyIII\Models\Account');
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank->account_id = $account->id;
        $start                 = Carbon::now()->startOfMonth();
        $end                   = Carbon::now()->endOfMonth()->startOfDay();
        $piggyBank->save();
        $this->be($account->user);

        $result = $this->object->createReminder($piggyBank, $start, $end);
        $this->assertEquals($piggyBank->targetamount, $result->metadata->leftToSave);
    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::createReminder
     */
    public function testCreateReminderHaveAlready()
    {
        $account                    = FactoryMuffin::create('FireflyIII\Models\Account');
        $piggyBank                  = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $reminder                   = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $piggyBank->account_id      = $account->id;
        $start                      = Carbon::now()->startOfMonth();
        $end                        = Carbon::now()->endOfMonth()->startOfDay();
        $reminder->remindersable_id = $piggyBank->id;
        $reminder->startdate        = $start;
        $reminder->enddate          = $end;
        $reminder->user_id          = $account->user_id;
        $reminder->save();
        $piggyBank->save();
        $this->be($account->user);

        $result = $this->object->createReminder($piggyBank, $start, $end);
        $this->assertEquals($reminder->id, $result->id);
    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::createReminder
     */
    public function testCreateReminderNoTarget()
    {
        $account               = FactoryMuffin::create('FireflyIII\Models\Account');
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank->targetdate = null;
        $piggyBank->account_id = $account->id;
        $start                 = Carbon::now()->startOfMonth();
        $end                   = Carbon::now()->endOfMonth()->startOfDay();
        $piggyBank->save();
        $this->be($account->user);

        $result = $this->object->createReminder($piggyBank, $start, $end);
        $this->assertEquals(0, $result->metadata->leftToSave);
    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::createReminders
     */
    public function testCreateReminders()
    {
        $account               = FactoryMuffin::create('FireflyIII\Models\Account');
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank->account_id = $account->id;
        $piggyBank->startdate  = new Carbon('2015-01-01');
        $piggyBank->targetdate = new Carbon('2015-12-31');
        $piggyBank->reminder   = 'monthly';
        $piggyBank->remind_me  = true;
        $piggyBank->save();
        $this->be($account->user);

        $this->object->createReminders($piggyBank, new Carbon('2015-05-05'));

        $this->assertCount(1, $piggyBank->reminders()->get());

    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::getReminderRanges
     */
    public function testGetReminderRangesNull()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $result    = $this->object->getReminderRanges($piggyBank);
        $this->assertEquals([], $result);
    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::getReminderRanges
     */
    public function testGetReminderRangesWithTargetDate()
    {
        /** @var \FireflyIII\Models\PiggyBank $piggyBank */
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank->startdate  = new Carbon('2015-01-01');
        $piggyBank->targetdate = new Carbon('2015-12-31');
        $piggyBank->reminder   = 'monthly';
        $piggyBank->remind_me  = true;
        $piggyBank->save();

        $result = $this->object->getReminderRanges($piggyBank, new Carbon('2015-04-01'));
        // date is ignored, result should be 12:
        $this->assertCount(12, $result);

    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::getReminderRanges
     */
    public function testGetReminderRangesWithoutTargetDate()
    {
        /** @var \FireflyIII\Models\PiggyBank $piggyBank */
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank->startdate  = new Carbon('2015-01-01');
        $piggyBank->targetdate = null;
        $piggyBank->reminder   = 'monthly';
        $piggyBank->remind_me  = true;
        $piggyBank->save();
        $result = $this->object->getReminderRanges($piggyBank, new Carbon('2015-12-31'));
        // date is a year later, result should be 12:
        $this->assertCount(12, $result);

    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::getReminderText
     */
    public function testGetReminderTextDate()
    {
        $piggyBank                  = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $reminder                   = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $piggyBank->targetdate      = new Carbon;
        $this->be($piggyBank->account->user);
        $reminder->remindersable_id = $piggyBank->id;

        Amount::shouldReceive('format')->andReturn('xx');

        $result = $this->object->getReminderText($reminder);
        $strpos = strpos($result, 'to fill this piggy bank on ');
        $this->assertTrue(!($strpos === false));

    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::getReminderText
     */
    public function testGetReminderTextNoPiggy()
    {
        $reminder                   = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $reminder->remindersable_id = 2;
        $this->assertEquals('Piggy bank no longer exists.', $this->object->getReminderText($reminder));

    }

    /**
     * @covers FireflyIII\Helpers\Reminders\ReminderHelper::getReminderText
     */
    public function testGetReminderTextNullDate()
    {
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $reminder              = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $piggyBank->targetdate = null;
        $piggyBank->save();
        $reminder->remindersable_id = $piggyBank->id;
        $reminder->save();

        Amount::shouldReceive('format')->andReturn('xx');

        $result = $this->object->getReminderText($reminder);
        $strpos = strpos($result, 'Add money to this piggy bank to reach your target of');
        $this->assertTrue(!($strpos === false));

    }
}
