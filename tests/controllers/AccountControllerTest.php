<?php
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
{
    /** @var  Account */
    public $account;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->createAccount();
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

    public function createAccount()
    {
        if (is_null($this->account)) {
            $this->account = FactoryMuffin::create('FireflyIII\Models\Account');
            Log::debug('Created a new account.');
            //$this->account->accountType->type = 'Asset account';
            //$this->account->accountType->save();
        }
    }

    public function testCreate()
    {
        $pref       = FactoryMuffin::create('FireflyIII\Models\Preference');
        $pref->data = '1M';
        $this->be($pref->user);


        Preferences::shouldReceive('get', 'viewRange')->andReturn($pref);

        // CURRENCY:
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        $this->call('GET', '/accounts/create/asset');
        $this->assertResponseOk();


        $this->assertViewHas('subTitle', 'Create a new asset account');
        $this->assertViewHas('subTitleIcon', 'fa-money');
        $this->assertViewHas('what', 'asset');

    }

    public function testDelete()
    {

        $this->be($this->account->user);
        $this->call('GET', '/accounts/delete/' . $this->account->id);
        $this->assertResponseOk();
        $this->assertViewHas('subTitle', 'Delete ' . strtolower(e($this->account->accountType->type)) . ' "' . e($this->account->name) . '"');

    }

    public function testDestroy()
    {
        // fake an account.
        $account = FactoryMuffin::create('FireflyIII\Models\Account');

        $this->be($account->user);

        // mock:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('destroy')->andReturn(true);

        // post it!
        $this->call('POST', '/accounts/destroy/' . $account->id, ['_token' => 'replaceme']);
        $this->assertSessionHas('success');
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        // fake an account.

        $this->be($this->account->user);
        $this->assertCount(1, DB::table('accounts')->where('id', $this->account->id)->whereNull('deleted_at')->get());

        // create a transaction journal that will act as opening balance:
        $openingBalance = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $repository     = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('openingBalanceTransaction')->andReturn($openingBalance);

        // create a transaction that will be returned for the opening balance transaction:
        $openingBalanceTransaction = FactoryMuffin::create('FireflyIII\Models\Transaction');
        $repository->shouldReceive('getFirstTransaction')->andReturn($openingBalanceTransaction);

        // CURRENCY:
        $currency = FactoryMuffin::create('FireflyIII\Models\TransactionCurrency');
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn($currency);
        Amount::shouldReceive('getAllCurrencies')->once()->andReturn([$currency]);
        Amount::shouldReceive('getCurrencyCode')->andReturn('X');

        // get edit page:
        $this->call('GET', '/accounts/edit/' . $this->account->id);

        // assert stuff:
        $this->assertResponseOk();
        $this->assertSessionHas('preFilled');
        $this->assertViewHas('subTitle', 'Edit ' . strtolower(e($this->account->accountType->type)) . ' "' . e($this->account->name) . '"');


    }

    public function testIndex()
    {
        // an account:
        $this->be($this->account->user);

        $collection = new Collection;
        $collection->push($this->account);

        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getAccounts')->andReturn($collection);
        $repository->shouldReceive('countAccounts')->andReturn(1);
        $repository->shouldReceive('getLastActivity')->andReturn(null);

        Amount::shouldReceive('format')->andReturn('');
        Amount::shouldReceive('getCurrencyCode')->andReturn('A');


        // put stuff in session:
        $this->session(['start' => new Carbon, 'end' => new Carbon]);

        // get edit page:
        $this->call('GET', '/accounts/asset');
        $this->assertResponseOk();
        $this->assertViewHas('what', 'asset');

    }

    public function testShow()
    {
        // an account:
        $this->be($this->account->user);

        // mock!
        Amount::shouldReceive('getCurrencyCode')->andReturn('A');
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('getJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        // get edit page:
        $this->call('GET', '/accounts/show/' . $this->account->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        // an account:
        $this->be($this->account->user);

        $data = [
            'name'                   => 'New test account ' . rand(1, 1000),
            'what'                   => 'asset',
            'virtualBalance'         => 0,
            'accountRole'            => 'defaultAsset',
            'openingBalance'         => 20,
            'openingBalanceDate'     => date('Y-m-d'),
            'openingBalanceCurrency' => 1,
            '_token'                 => 'replaceme'
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\AccountFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake store routine:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('store')->andReturn($this->account);

        $this->call('POST', '/accounts/store', $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    public function testStoreAndRedirect()
    {
        // an account:
        $this->be($this->account->user);

        $data = [
            'name'                   => 'New test account ' . rand(1, 1000),
            'what'                   => 'asset',
            'virtualBalance'         => 0,
            'accountRole'            => 'defaultAsset',
            'openingBalance'         => 20,
            'openingBalanceDate'     => date('Y-m-d'),
            'openingBalanceCurrency' => 1,
            '_token'                 => 'replaceme',
            'create_another'         => 1,
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\AccountFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake store routine:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('store')->andReturn($this->account);

        $this->call('POST', '/accounts/store', $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');

    }

    public function testUpdate()
    {

        // an account:
        $this->be($this->account->user);

        $data = [
            'name'                   => 'Edited test account ' . rand(1, 1000),
            'active'                 => 1,
            'accountRole'            => 'defaultAsset',
            'virtualBalance'         => 0,
            'openingBalance'         => 25,
            'openingBalanceDate'     => date('Y-m-d'),
            'openingBalanceCurrency' => 1,
            '_token'                 => 'replaceme'
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\AccountFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake update routine:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('update')->andReturn($this->account);

        $this->call('POST', '/accounts/update/' . $this->account->id, $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    public function testUpdateAndRedirect()
    {

        // an account:
        $this->be($this->account->user);

        $data = [
            'name'                   => 'Edited test account ' . rand(1, 1000),
            'active'                 => 1,
            'accountRole'            => 'defaultAsset',
            'virtualBalance'         => 0,
            'openingBalance'         => 25,
            'openingBalanceDate'     => date('Y-m-d'),
            'openingBalanceCurrency' => 1,
            '_token'                 => 'replaceme',
            'return_to_edit'         => 1,
        ];

        // fake validation routine:
        $request = $this->mock('FireflyIII\Http\Requests\AccountFormRequest');
        $request->shouldReceive('input')->andReturn('');

        // fake update routine:
        $repository = $this->mock('FireflyIII\Repositories\Account\AccountRepositoryInterface');
        $repository->shouldReceive('update')->andReturn($this->account);

        $this->call('POST', '/accounts/update/' . $this->account->id, $data);
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

}
