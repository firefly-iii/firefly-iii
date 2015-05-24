<?php
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * Class CurrencyControllerTest
 */
class CurrencyControllerTest extends TestCase
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
     * @covers FireflyIII\Http\Controllers\CurrencyController::create
     */
    public function testCreate()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $this->call('GET', '/currency/create');
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Create a new currency');
        $this->assertViewHas('subTitleIcon', 'fa-plus');

    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::defaultCurrency
     */
    public function testDefaultCurrency()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');

        $this->call('GET', '/currency/default/' . $currency->id);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', $currency->name . ' is now the default currency.');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::delete
     */
    public function testDelete()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $repository->shouldReceive('countJournals')->andReturn(0);

        $this->call('GET', '/currency/delete/' . $currency->id);
        $this->assertResponseOk();
        $this->assertViewHas('currency');

    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::delete
     */
    public function testDeleteUnable()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $repository->shouldReceive('countJournals')->andReturn(1);

        $this->call('GET', '/currency/delete/' . $currency->id);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('error');

    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::destroy
     */
    public function testDestroy()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $repository->shouldReceive('countJournals')->andReturn(0);

        $this->call('POST', '/currency/destroy/' . $currency->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success', 'Currency "' . e($currency->name) . '" deleted');

    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::destroy
     */
    public function testDestroyUnable()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $repository->shouldReceive('countJournals')->andReturn(1);

        $this->call('POST', '/currency/destroy/' . $currency->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('error');

    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::edit
     */
    public function testEdit()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $repository->shouldReceive('countJournals')->andReturn(0);

        $this->call('GET', '/currency/edit/' . $currency->id);
        $this->assertResponseOk();
        $this->assertViewHas('currency');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::index
     */
    public function testIndex()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $repository->shouldReceive('get')->andReturn(new Collection);
        $repository->shouldReceive('getCurrencyByPreference')->andReturn($currency);

        $this->call('GET', '/currency');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::store
     */
    public function testStore()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CurrencyFormRequest');
        $request->shouldReceive('getCurrencyData')->andReturn([]);
        $repository->shouldReceive('store')->andReturn($currency);

        $this->call('POST', '/currency/store', ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');


    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::store
     */
    public function testStoreAndReturn()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CurrencyFormRequest');
        $request->shouldReceive('getCurrencyData')->andReturn([]);
        $repository->shouldReceive('store')->andReturn($currency);

        $this->call('POST', '/currency/store', ['_token' => 'replaceMe', 'create_another' => 1]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');


    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::update
     */
    public function testUpdate()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CurrencyFormRequest');
        $request->shouldReceive('getCurrencyData')->andReturn([]);
        $repository->shouldReceive('update')->andReturn($currency);

        $this->call('POST', '/currency/update/' . $currency->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    /**
     * @covers FireflyIII\Http\Controllers\CurrencyController::update
     */
    public function testUpdateAndReturn()
    {
        $user     = FactoryMuffin::create('FireflyIII\User');
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        $this->be($user);

        $repository = $this->mock('FireflyIII\Repositories\Currency\CurrencyRepositoryInterface');
        $request    = $this->mock('FireflyIII\Http\Requests\CurrencyFormRequest');
        $request->shouldReceive('getCurrencyData')->andReturn([]);
        $repository->shouldReceive('update')->andReturn($currency);

        $this->call('POST', '/currency/update/' . $currency->id, ['_token' => 'replaceMe', 'return_to_edit' => 1]);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }
}
