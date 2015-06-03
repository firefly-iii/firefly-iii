<?php

use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class HomeControllerTest
 */
class HomeControllerTest extends TestCase
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

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::dateRange
     */
    public function testDateRange()
    {
        $start = '2015-03-01';
        $end   = '2015-03-31';
        $user  = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);


        $this->call('POST', '/daterange', ['end' => $end, 'start' => $start, '_token' => 'replaceme']);
        $this->assertResponseOk();

        $this->assertSessionHas('start');
        $this->assertSessionHas('end');

    }


    /**
     * @covers FireflyIII\Http\Controllers\HomeController::dateRange
     */
    public function testDateRangeWarning()
    {
        $start = '2014-03-01';
        $end   = '2015-03-31';
        $user  = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('POST', '/daterange', ['end' => $end, 'start' => $start, '_token' => 'replaceme']);
        $this->assertResponseOk();

        $this->assertSessionHas('start');
        $this->assertSessionHas('end');
        $this->assertSessionHas('warning');
    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::flush
     */
    public function testFlush()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/flush');
        $this->assertResponseStatus(302);

    }

    /**
     * @covers FireflyIII\Http\Controllers\HomeController::index
     */
    public function testIndex()
    {
        $user       = FactoryMuffin::create('FireflyIII\User');
        $preference = FactoryMuffin::create('FireflyIII\Models\Preference');
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journals   = new Collection([$journal]);
        $account    = FactoryMuffin::create('FireflyIII\Models\Account');
        $accounts   = new Collection([$account]);
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');

        $this->be($user);

        // mock ALL THE THINGS!
        $repository->shouldReceive('countAccounts')->once()->andReturn(3);
        Preferences::shouldReceive('get')->once()->withArgs(['frontPageAccounts', []])->andReturn($preference);
        $repository->shouldReceive('getFrontpageAccounts')->once()->with($preference)->andReturn($accounts);
        $repository->shouldReceive('getSavingsAccounts')->once()->andReturn($accounts);
        $repository->shouldReceive('getPiggyBankAccounts')->once()->andReturn($accounts);
        $repository->shouldReceive('sumOfEverything')->once()->andReturn(1);
        $repository->shouldReceive('getFrontpageTransactions')->once()->andReturn($journals);

        // language preference:
        $language       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $language->data = 'en';
        $language->save();
        Preferences::shouldReceive('get')->withAnyArgs()->andReturn($language);

        $lastActivity       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $lastActivity->data = microtime();
        Preferences::shouldReceive('lastActivity')->andReturn($lastActivity);


        Amount::shouldReceive('getCurrencyCode')->andReturn('EUR');
        Amount::shouldReceive('format')->andReturn('xxx');
        Amount::shouldReceive('formatJournal')->with($journal)->andReturn('xxx');

        $this->call('GET', '/');
        $this->assertResponseOk();

    }

}
