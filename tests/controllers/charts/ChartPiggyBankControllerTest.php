<?php

use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartPiggyBankControllerTest
 */
class ChartPiggyBankControllerTest extends TestCase
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
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testHistory()
    {
        $piggy = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggy->account->user);

        // data:
        $obj       = new stdClass;
        $obj->sum  = 100;
        $obj->date = '2015-01-01';
        $set       = [
            $obj
        ];

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');

        // fake!
        $repository->shouldReceive('getEventSummarySet')->andReturn($set);

        $this->call('GET', '/chart/piggyBank/' . $piggy->id);
        $this->assertResponseOk();
    }
}
