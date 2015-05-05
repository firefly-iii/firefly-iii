<?php
use Carbon\Carbon;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class BillControllerTest
 */
class BillControllerTest extends TestCase
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

    public function testAdd()
    {
        // create a bill:
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);

        // create an expense account:
        $expense = FactoryMuffin::create('FireflyIII\Models\Account');
        // fix the name of the expense account to match one of the words
        // in the bill:
        $words         = explode(',', $bill->match);
        $word          = $words[1];
        $expense->name = $word;
        $expense->save();

        // mock repository:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->andReturn([$expense]);

        // go!
        $this->call('GET', '/bills/add/' . $bill->id);
        $this->assertSessionHas('preFilled');
        $this->assertRedirectedToRoute('transactions.create', ['withdrawal']);
    }

    public function testCreate()
    {
        // go!
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);

        // CURRENCY:
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('GET', '/bills/create');
        $this->assertViewHas('subTitle', 'Create new bill');
        $this->assertResponseOk();

    }

    public function testDelete()
    {
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);
        $this->call('GET', '/bills/delete/' . $bill->id);
        $this->assertViewHas('subTitle', 'Delete "' . e($bill->name) . '"');
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);

        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $repository->shouldReceive('destroy')->andReturn(true);


        $this->call('POST', '/bills/destroy/' . $bill->id, ['_token' => 'replaceMe']);
        $this->assertSessionHas('success', 'The bill was deleted.');
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);

        // CURRENCY:
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('GET', '/bills/edit/' . $bill->id);
        $this->assertViewHas('subTitle', 'Edit "' . e($bill->name) . '"');
        $this->assertResponseOk();
    }

    public function testIndex()
    {
        $bill = FactoryMuffin::create('FireflyIII\Models\Bill');
        $this->be($bill->user);

        $collection = new Collection;
        $collection->push($bill);

        Amount::shouldReceive('format')->andReturn('XX');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $repository->shouldReceive('getBills')->once()->andReturn($collection);
        $repository->shouldReceive('nextExpectedMatch')->with($bill)->andReturn(new Carbon);
        $repository->shouldReceive('lastFoundMatch')->with($bill)->andReturn(new Carbon);

        $this->call('GET', '/bills');
        $this->assertResponseOk();

    }

    public function testRescan()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $collection = new Collection;
        $this->be($bill->user);
        $collection->push($journal);

        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $repository->shouldReceive('getPossiblyRelatedJournals')->once()->andReturn($collection);
        $repository->shouldReceive('scan');

        $this->call('GET', '/bills/rescan/' . $bill->id);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Rescanned everything.');


    }

    public function testRescanInactive()
    {
        $bill         = FactoryMuffin::create('FireflyIII\Models\Bill');
        $bill->active = 0;
        $bill->save();
        $this->be($bill->user);

        $this->call('GET', '/bills/rescan/' . $bill->id);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('warning', 'Inactive bills cannot be scanned.');

    }

    public function testShow()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $journal    = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $collection = new Collection;

        $bill->save();
        $this->be($bill->user);
        $collection->push($journal);


        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $repository->shouldReceive('getJournals')->once()->andReturn($collection);
        $repository->shouldReceive('nextExpectedMatch')->once()->andReturn(new Carbon);

        Amount::shouldReceive('format')->andReturn('XX');
        Amount::shouldReceive('getCurrencyCode')->andReturn('XX');


        $this->call('GET', '/bills/show/' . $bill->id);
    }

    public function testStore()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\BillFormRequest');

        $this->be($bill->user);
        $request->shouldReceive('getBillData')->once()->andReturn([]);
        $repository->shouldReceive('store')->with([])->andReturn($bill);

        $this->call('POST', '/bills/store', ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Bill "' . e($bill->name) . '" stored.');
    }

    public function testStoreAndRedirect()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\BillFormRequest');

        $this->be($bill->user);
        $request->shouldReceive('getBillData')->once()->andReturn([]);
        $repository->shouldReceive('store')->with([])->andReturn($bill);

        $this->call('POST', '/bills/store', ['_token' => 'replaceMe', 'create_another' => 1]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Bill "' . e($bill->name) . '" stored.');
    }

    public function testUpdate()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\BillFormRequest');

        $this->be($bill->user);
        $request->shouldReceive('getBillData')->once()->andReturn([]);
        $repository->shouldReceive('update')->andReturn($bill);

        $this->call('POST', '/bills/update/' . $bill->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Bill "' . e($bill->name) . '" updated.');
    }

    public function testUpdateAndRedirect()
    {
        $bill       = FactoryMuffin::create('FireflyIII\Models\Bill');
        $repository = $this->mock('FireflyIII\Repositories\Bill\BillRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\BillFormRequest');

        $this->be($bill->user);
        $request->shouldReceive('getBillData')->once()->andReturn([]);
        $repository->shouldReceive('update')->andReturn($bill);

        $this->call('POST', '/bills/update/' . $bill->id, ['_token' => 'replaceMe', 'return_to_edit' => 1]);
        $this->assertResponseStatus(302);

    }
}
