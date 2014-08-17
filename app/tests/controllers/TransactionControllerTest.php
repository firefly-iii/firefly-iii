<?php

use Mockery as m;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

/**
 * Class TransactionControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class TransactionControllerTest extends TestCase
{
    protected $_user;
    protected $_repository;

    protected $_accounts;
    protected $_budgets;
    protected $_piggies;

    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
        $this->_user = m::mock('User', 'Eloquent');
        $this->_repository = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $this->_accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $this->_budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $this->_piggies = $this->mock('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

    }

    public function tearDown()
    {
        m::close();
    }

    public function testCreateDeposit()
    {
        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);
        $this->_piggies->shouldReceive('get')->andReturn([]);

        $this->action('GET', 'TransactionController@create', ['what' => 'deposit']);
        $this->assertResponseOk();
    }

    public function testCreateTransfer()
    {
        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);
        $this->_piggies->shouldReceive('get')->andReturn([]);
        $this->action('GET', 'TransactionController@create', ['what' => 'transfer']);
        $this->assertResponseOk();
    }

    public function testCreateWithdrawal()
    {
        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);
        $this->_piggies->shouldReceive('get')->andReturn([]);
        $this->action('GET', 'TransactionController@create', ['what' => 'withdrawal']);
        $this->assertResponseOk();
    }

    public function testDelete()
    {
        $journal = f::create('TransactionJournal');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'TransactionController@delete', $journal->id);
        $this->assertResponseOk();
    }

    public function testDestroy()
    {
        $journal = f::create('TransactionJournal');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('POST', 'TransactionController@destroy', $journal->id);
        $this->assertResponseStatus(302);
    }

    public function testEdit()
    {
        $journal = f::create('TransactionJournal');
        $type = f::create('TransactionType');
        $type->type = 'Withdrawal';
        $type->save();
        $journal->transactiontype()->associate($type);
        $journal->save();

        $category = f::create('Category');
        $journal->categories()->save($category);

        $budget = f::create('Budget');
        $journal->budgets()->save($budget);

        $one = f::create('Transaction');
        $two = f::create('Transaction');
        $one->transactionjournal()->associate($journal);
        $two->transactionjournal()->associate($journal);
        $one->save();
        $two->save();

        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'TransactionController@edit', $journal->id);
        $this->assertResponseOk();
    }

    public function testEditDeposit()
    {
        $journal = f::create('TransactionJournal');
        $type = f::create('TransactionType');
        $type->type = 'Deposit';
        $type->save();
        $journal->transactiontype()->associate($type);
        $journal->save();

        $category = f::create('Category');
        $journal->categories()->save($category);

        $budget = f::create('Budget');
        $journal->budgets()->save($budget);

        $one = f::create('Transaction');
        $two = f::create('Transaction');
        $one->transactionjournal()->associate($journal);
        $two->transactionjournal()->associate($journal);
        $one->save();
        $two->save();

        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'TransactionController@edit', $journal->id);
        $this->assertResponseOk();
    }

    public function testEditTransfer()
    {
        $journal = f::create('TransactionJournal');
        $type = f::create('TransactionType');
        $type->type = 'Transfer';
        $type->save();
        $journal->transactiontype()->associate($type);
        $journal->save();

        $category = f::create('Category');
        $journal->categories()->save($category);

        $budget = f::create('Budget');
        $journal->budgets()->save($budget);

        $one = f::create('Transaction');
        $two = f::create('Transaction');
        $one->transactionjournal()->associate($journal);
        $two->transactionjournal()->associate($journal);
        $one->save();
        $two->save();

        $this->_accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);
        $this->_budgets->shouldReceive('getAsSelectList')->andReturn([]);

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'TransactionController@edit', $journal->id);
        $this->assertResponseOk();
    }

    public function testIndex()
    {

        $journal = f::create('TransactionJournal');
        $type = f::create('TransactionType');
        $type->type = 'Withdrawal';
        $type->save();
        $journal->transactiontype()->associate($type);
        $journal->save();

        $one = f::create('Transaction');
        $two = f::create('Transaction');
        $one->transactionjournal()->associate($journal);
        $two->transactionjournal()->associate($journal);
        $one->save();
        $two->save();

        // make a paginator
        $paginator = Paginator::make([$journal], 1, 1);


        $this->_repository->shouldReceive('paginate')->with(25)->andReturn($paginator);
        $this->_repository->shouldReceive('get')->andReturn([]);
        $this->action('GET', 'TransactionController@index');
        $this->assertResponseOk();
    }

    public function testShow()
    {
        $journal = f::create('TransactionJournal');

        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->action('GET', 'TransactionController@show', $journal->id);
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $journal = f::create('TransactionJournal');

        $this->_repository->shouldReceive('store')->andReturn($journal);

        $this->action('POST', 'TransactionController@store', ['what' => 'deposit']);
        $this->assertResponseStatus(302);
    }

    public function testStoreFails()
    {
        $journal = f::create('TransactionJournal');
        unset($journal->description);

        $this->_repository->shouldReceive('store')->andReturn($journal);

        $this->action('POST', 'TransactionController@store', ['what' => 'deposit', 'create' => '1']);
        $this->assertResponseStatus(302);
    }

    public function testStoreRedirect()
    {
        $journal = f::create('TransactionJournal');

        $this->_repository->shouldReceive('store')->andReturn($journal);

        $this->action('POST', 'TransactionController@store', ['what' => 'deposit', 'create' => '1']);
        $this->assertResponseStatus(302);
    }

    public function testUpdate()
    {
        $journal = f::create('TransactionJournal');


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $this->_repository->shouldReceive('update')->andReturn($journal);

        $this->action('POST', 'TransactionController@update', $journal->id);
        $this->assertResponseStatus(302);
    }

    public function testUpdateFailed()
    {
        $journal = f::create('TransactionJournal');


        // for binding
        Auth::shouldReceive('user')->andReturn($this->_user);
        Auth::shouldReceive('check')->andReturn(true);
        $this->_user->shouldReceive('getAttribute')->with('id')->once()->andReturn($journal->user_id);
        $this->_user->shouldReceive('getAttribute')->with('email')->andReturn('some@email');

        $journal->description = null;

        $this->_repository->shouldReceive('update')->andReturn($journal);

        $this->action('POST', 'TransactionController@update', $journal->id);
        $this->assertResponseStatus(302);
    }
} 