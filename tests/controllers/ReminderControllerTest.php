<?php
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ReportControllerTest
 */
class ReminderControllerTest extends TestCase
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

    public function testAct()
    {
        $reminder = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $this->be($reminder->remindersable->account->user);

        $this->call('GET', '/reminder/act/' . $reminder->id);
        $this->assertResponseStatus(302);
        $this->assertRedirectedToRoute('transactions.create', ['transfer']);

    }

    public function testDismiss()
    {
        $reminder = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $this->be($reminder->remindersable->account->user);

        $this->call('GET', '/reminder/dismiss/' . $reminder->id);
        $this->assertResponseStatus(302);
        $this->assertRedirectedTo('/');
    }

    public function testIndex()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $reminder   = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $collection = new Collection([$reminder]);

        $repository = $this->mock('FireflyIII\Repositories\Reminder\ReminderRepositoryInterface');

        $repository->shouldReceive('getActiveReminders')->andReturn($collection);
        $repository->shouldReceive('getExpiredReminders')->andReturn($collection);
        $repository->shouldReceive('getInactiveReminders')->andReturn($collection);
        $repository->shouldReceive('getDismissedReminders')->andReturn($collection);

        $this->call('GET', '/reminders');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $reminder         = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $reminder->notnow = false;
        $reminder->save();
        $this->be($reminder->remindersable->account->user);

        $this->call('GET', '/reminder/' . $reminder->id);
        $this->assertResponseOk();
    }

    public function testShowDismissed()
    {
        $reminder         = FactoryMuffin::create('FireflyIII\Models\Reminder');
        $reminder->notnow = true;
        $reminder->save();
        $this->be($reminder->remindersable->account->user);

        $this->call('GET', '/reminder/' . $reminder->id);
        $this->assertResponseOk();
    }

}
