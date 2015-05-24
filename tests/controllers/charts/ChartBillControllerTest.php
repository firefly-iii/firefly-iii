<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ChartBillControllerTest
 */
class ChartBillControllerTest extends TestCase
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
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);


    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BillController::frontpage
     */
    public function testFrontpage()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // set!
        $bills       = new Collection([FactoryMuffin::create('FireflyIII\Models\Bill'), FactoryMuffin::create('FireflyIII\Models\Bill')]);
        $journals    = new Collection(
            [FactoryMuffin::create('FireflyIII\Models\TransactionJournal'), FactoryMuffin::create('FireflyIII\Models\TransactionJournal')]
        );
        $creditCards = new Collection([FactoryMuffin::create('FireflyIII\Models\Account'), FactoryMuffin::create('FireflyIII\Models\Account')]);
        $ranges      = [['start' => new Carbon, 'end' => new Carbon]];

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');


        // fake!
        $repository->shouldReceive('getActiveBills')->andReturn($bills);
        $repository->shouldReceive('getRanges')->andReturn($ranges);
        $repository->shouldReceive('getJournalsInRange')->andReturn(new Collection, $journals);
        $accounts->shouldReceive('getCreditCards')->andReturn($creditCards);
        $accounts->shouldReceive('getTransfersInRange')->andReturn(new Collection);
        $repository->shouldReceive('createFakeBill')->andReturn($bills->first());
        Steam::shouldReceive('balance')->andReturn(-10, 0);


        $this->call('GET', '/chart/bill/frontpage');
        $this->assertResponseOk();

    }

    /**
     * @covers FireflyIII\Http\Controllers\Chart\BillController::single
     */
    public function testSingle()
    {
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);

        // set
        $journals = new Collection([FactoryMuffin::create('FireflyIII\Models\TransactionJournal')]);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $repository->shouldReceive('getJournals')->andReturn($journals);

        // fake!

        $this->call('GET', '/chart/bill/' . $bill->id);
        $this->assertResponseOk();

    }


}
