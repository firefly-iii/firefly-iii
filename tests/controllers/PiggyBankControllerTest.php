<?php
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * Class PiggyBankControllerTest
 */
class PiggyBankControllerTest extends TestCase
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
     * @covers FireflyIII\Http\Controllers\PiggyBankController::add
     */
    public function testAdd()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);


        // mock
        /** @var Mockery\MockInterface $repository */
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('leftOnAccount')->withAnyArgs()->andReturn(12);
        Amount::shouldReceive('format')->andReturn('XXxx');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('X');

        $this->call('GET', '/piggy-banks/add/' . $piggyBank->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::create
     */
    public function testCreate()
    {
        $account    = FactoryMuffin::create('FireflyIII\Models\Account');
        $collection = new Collection([$account]);
        $user       = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->once()->with(['Default account', 'Asset account'])->andReturn($collection);
        ExpandedForm::shouldReceive('makeSelectList')->with($collection)->andReturn([]);

        // also cover the view now that we've touched ExpandedForm:
        ExpandedForm::shouldReceive('text')->andReturn('');
        ExpandedForm::shouldReceive('select')->andReturn('');
        ExpandedForm::shouldReceive('amount')->andReturn('');
        ExpandedForm::shouldReceive('date')->andReturn('');
        ExpandedForm::shouldReceive('checkbox')->andReturn('');
        ExpandedForm::shouldReceive('optionsList')->andReturn('');

        $this->call('GET', '/piggy-banks/create');
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::delete
     */
    public function testDelete()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);


        $this->call('GET', '/piggy-banks/delete/' . $piggyBank->id);
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Delete piggy bank "' . e($piggyBank->name) . '"');
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::destroy
     */
    public function testDestroy()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $repository = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $repository->shouldReceive('destroy')->once()->withAnyArgs()->andReturn(true);


        $this->call('POST', '/piggy-banks/destroy/' . $piggyBank->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::edit
     */
    public function testEdit()
    {
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank->targetdate = Carbon::now()->addYear();
        $piggyBank->save();
        $this->be($piggyBank->account->user);
        $account    = FactoryMuffin::create('FireflyIII\Models\Account');
        $collection = new Collection([$account]);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->once()->with(['Default account', 'Asset account'])->andReturn($collection);
        ExpandedForm::shouldReceive('makeSelectList')->with($collection)->andReturn([]);

        // also cover the view now that we've touched ExpandedForm:
        ExpandedForm::shouldReceive('text')->andReturn('');
        ExpandedForm::shouldReceive('select')->andReturn('');
        ExpandedForm::shouldReceive('amount')->andReturn('');
        ExpandedForm::shouldReceive('date')->andReturn('');
        ExpandedForm::shouldReceive('checkbox')->andReturn('');
        ExpandedForm::shouldReceive('optionsList')->andReturn('');


        $this->call('GET', '/piggy-banks/edit/' . $piggyBank->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::edit
     */
    public function testEditNullDate()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);
        $piggyBank->targetdate = null;
        $piggyBank->save();
        $account    = FactoryMuffin::create('FireflyIII\Models\Account');
        $collection = new Collection([$account]);

        // mock!
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->once()->with(['Default account', 'Asset account'])->andReturn($collection);
        ExpandedForm::shouldReceive('makeSelectList')->with($collection)->andReturn([]);

        // also cover the view now that we've touched ExpandedForm:
        ExpandedForm::shouldReceive('text')->andReturn('');
        ExpandedForm::shouldReceive('select')->andReturn('');
        ExpandedForm::shouldReceive('amount')->andReturn('');
        ExpandedForm::shouldReceive('date')->andReturn('');
        ExpandedForm::shouldReceive('checkbox')->andReturn('');
        ExpandedForm::shouldReceive('optionsList')->andReturn('');


        $this->call('GET', '/piggy-banks/edit/' . $piggyBank->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::index
     */
    public function testIndex()
    {
        $piggyBank1             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank2             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank3             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank2->account_id = $piggyBank1->account_id;
        $user                   = FactoryMuffin::create('FireflyIII\User');

        $piggyBank2->save();

        $collection = new Collection([$piggyBank1, $piggyBank2, $piggyBank3]);
        $this->be($user);

        // mock!
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');

        // act!
        $piggyBanks->shouldReceive('getPiggyBanks')->once()->andReturn($collection);
        Steam::shouldReceive('balance')->andReturn(20);
        $accounts->shouldReceive('leftOnAccount')->andReturn(12);
        Amount::shouldReceive('format')->andReturn('123');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');


        $this->call('GET', '/piggy-banks');
        $this->assertResponseOk();

    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::order
     */
    public function testOrder()
    {
        $piggyBank1 = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $piggyBank2 = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $user       = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // mock!
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $piggyBanks->shouldReceive('reset')->once();
        $piggyBanks->shouldReceive('setOrder');
        $array = [
            $piggyBank1->id => 0,
            $piggyBank2->id => 1,
        ];

        $this->call('POST', '/piggy-banks/sort', ['_token' => 'replaceMe', 'order' => $array]);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::postAdd
     */
    public function testPostAdd()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        // mock!
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $accounts->shouldReceive('leftOnAccount')->andReturn(20);
        $piggyBanks->shouldReceive('createEvent')->once();

        Amount::shouldReceive('format')->andReturn('something');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('POST', '/piggy-banks/add/' . $piggyBank->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::postAdd
     */
    public function testPostAddOverdraw()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        // mock!
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('leftOnAccount')->andReturn(20);

        Amount::shouldReceive('format')->andReturn('something');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('POST', '/piggy-banks/add/' . $piggyBank->id, ['_token' => 'replaceMe', 'amount' => '10000']);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::postRemove
     */
    public function testPostRemove()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        // mock!
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $accounts->shouldReceive('leftOnAccount')->andReturn(20);
        $piggyBanks->shouldReceive('createEvent')->once();

        Amount::shouldReceive('format')->andReturn('something');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('something');

        $this->call('POST', '/piggy-banks/remove/' . $piggyBank->id, ['_token' => 'replaceMe']);
        $this->assertResponseStatus(302);
    }

    public function testPostRemoveOverdraw()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        // mock!
        $accounts   = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('leftOnAccount')->andReturn(20);

        Amount::shouldReceive('format')->andReturn('something');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('something');

        $this->call('POST', '/piggy-banks/remove/' . $piggyBank->id, ['_token' => 'replaceMe', 'amount' => '10000']);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::remove
     */
    public function testRemove()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        Amount::shouldReceive('format')->andReturn('something');
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('X');

        $this->call('GET', '/piggy-banks/remove/' . $piggyBank->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::show
     */
    public function testShow()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $piggyBanks->shouldReceive('getEvents')->andReturn(new Collection);
        Amount::shouldReceive('format')->andReturn('something');
        Amount::shouldReceive('getCurrencySymbol')->andReturn('something');
        Amount::shouldReceive('getCurrencyCode')->andReturn('something');


        $this->call('GET', '/piggy-banks/show/' . $piggyBank->id);
        $this->assertResponseOk();
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::store
     */
    public function testStore()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $piggyBankData = [
            'name'         => 'Some name' . rand(1, 100),
            'account_id'   => $piggyBank->account_id,
            'targetamount' => 100,
            'targetdate'   => '',
            'reminder'     => 'month',
            '_token'       => 'replaceMe'
        ];

        // mock!
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $piggyBanks->shouldReceive('store')->once()->andReturn($piggyBank);

        $this->call('POST', '/piggy-banks/store', $piggyBankData);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::store
     */
    public function testStoreCreateAnother()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $piggyBankData = [
            'name'           => 'Some name' . rand(1, 100),
            'account_id'     => $piggyBank->account_id,
            'targetamount'   => 100,
            'targetdate'     => '',
            'reminder'       => 'month',
            'create_another' => 1,
            '_token'         => 'replaceMe'
        ];

        // mock!
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $piggyBanks->shouldReceive('store')->once()->andReturn($piggyBank);

        $this->call('POST', '/piggy-banks/store', $piggyBankData);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::update
     */
    public function testUpdate()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $piggyBankData = [
            'name'         => 'Some name' . rand(1, 100),
            'account_id'   => $piggyBank->account_id,
            'targetamount' => 200,
            'targetdate'   => '',
            'reminder'     => 'month',
            '_token'       => 'replaceMe'
        ];

        // mock!
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $piggyBanks->shouldReceive('update')->once()->andReturn($piggyBank);

        $this->call('POST', '/piggy-banks/update/' . $piggyBank->id, $piggyBankData);
        $this->assertResponseStatus(302);
    }

    /**
     * @covers FireflyIII\Http\Controllers\PiggyBankController::update
     */
    public function testUpdateReturnToEdit()
    {
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $this->be($piggyBank->account->user);

        $piggyBankData = [
            'name'           => 'Some name' . rand(1, 100),
            'account_id'     => $piggyBank->account_id,
            'targetamount'   => 200,
            'targetdate'     => '',
            'return_to_edit' => 1,
            'reminder'       => 'month',
            '_token'         => 'replaceMe'
        ];

        // mock!
        $piggyBanks = $this->mock('FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface');
        $piggyBanks->shouldReceive('update')->once()->andReturn($piggyBank);

        $this->call('POST', '/piggy-banks/update/' . $piggyBank->id, $piggyBankData);
        $this->assertResponseStatus(302);
    }
}
