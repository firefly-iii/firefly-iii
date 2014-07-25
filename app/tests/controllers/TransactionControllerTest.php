<?php

use League\FactoryMuffin\Facade\FactoryMuffin;

class TransactionControllerTest extends TestCase
{
    /**
     * Default preparation for each test
     */
    public function setUp()
    {
        parent::setUp();

        $this->prepareForTests();
    }

    /**
     * Migrate the database
     */
    private function prepareForTests()
    {
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    public function testCreateWithdrawal()
    {

        $set = [0 => '(no budget)'];
        View::shouldReceive('share');
        View::shouldReceive('make')->with('transactions.create')->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()
            ->with('what', 'withdrawal')
            ->andReturn(Mockery::self())
            ->shouldReceive('with')->once()
            ->with('accounts', [])
            ->andReturn(Mockery::self())
            ->shouldReceive('with')->once()
            ->with('budgets', $set)->andReturn(Mockery::self());

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);

        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('getAsSelectList')->andReturn($set);


        // call
        $this->call('GET', '/transactions/create/withdrawal');

        // test
        $this->assertResponseOk();
    }

    public function testCreateDeposit()
    {

        $set = [0 => '(no budget)'];
        View::shouldReceive('share');
        View::shouldReceive('make')->with('transactions.create')->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()
            ->with('what', 'deposit')
            ->andReturn(Mockery::self())

            ->shouldReceive('with')->once()
            ->with('accounts', [])
            ->andReturn(Mockery::self())
            ->shouldReceive('with')->once()
            ->with('budgets', $set)->andReturn(Mockery::self());

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);

        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('getAsSelectList')->andReturn($set);


        // call
        $this->call('GET', '/transactions/create/deposit');

        // test
        $this->assertResponseOk();
    }

    public function testCreateTransfer()
    {

        $set = [0 => '(no budget)'];
        View::shouldReceive('share');
        View::shouldReceive('make')->with('transactions.create')->andReturn(\Mockery::self())
            ->shouldReceive('with')->once()
            ->with('what', 'transfer')
            ->andReturn(Mockery::self())
            ->shouldReceive('with')->once()
            ->with('accounts', [])
            ->andReturn(Mockery::self())
            ->shouldReceive('with')->once()
            ->with('budgets', $set)->andReturn(Mockery::self());

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);

        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('getAsSelectList')->andReturn($set);


        // call
        $this->call('GET', '/transactions/create/transfer');

        // test
        $this->assertResponseOk();
    }


    public function testPostCreateWithdrawal()
    {
        // create objects.
        $account = FactoryMuffin::create('Account');
        $beneficiary = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');
        $budget = FactoryMuffin::create('Budget');


        // data to send:
        $data = [
            'beneficiary' => $beneficiary->name,
            'category'    => $category->name,
            'budget_id'   => $budget->id,
            'account_id'  => $account->id,
            'description' => 'Bla',
            'amount'      => 1.2,
            'date'        => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('createOrFindBeneficiary')->with($beneficiary->name)->andReturn($beneficiary);
        $accounts->shouldReceive('find')->andReturn($account);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with($budget->name)->andReturn($budget);
        $budgets->shouldReceive('find')->andReturn($budget);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->once()->andReturn($journal);

        // call
        $this->call('POST', '/transactions/store/withdrawal', $data);

        // test
        $this->assertRedirectedToRoute('index');
    }

    public function testPostCreateDeposit()
    {
        // create objects.
        $account = FactoryMuffin::create('Account');
        $beneficiary = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');


        // data to send:
        $data = [
            'beneficiary' => $beneficiary->name,
            'category'    => $category->name,
            'account_id'  => $account->id,
            'description' => 'Bla',
            'amount'      => 1.2,
            'date'        => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('createOrFindBeneficiary')->with($beneficiary->name)->andReturn($beneficiary);
        $accounts->shouldReceive('find')->andReturn($account);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->once()->andReturn($journal);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with('')->andReturn(null);
        $budgets->shouldReceive('find')->andReturn(null);

        // call
        $this->call('POST', '/transactions/store/deposit', $data);

        // test
        $this->assertRedirectedToRoute('index');
    }

    public function testPostCreateTransfer()
    {
        // create objects.
        $from = FactoryMuffin::create('Account');
        $to = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');


        // data to send:
        $data = [
            'category'        => $category->name,
            'account_from_id' => $from->id,
            'account_to_id'   => $to->id,
            'description'     => 'Bla',
            'amount'          => 1.2,
            'date'            => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('find')->with($from->id)->andReturn($from);
        $accounts->shouldReceive('find')->with($to->id)->andReturn($to);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->once()->andReturn($journal);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with('')->andReturn(null);
        $budgets->shouldReceive('find')->andReturn(null);

        // call
        $this->call('POST', '/transactions/store/transfer', $data);

        // test
        $this->assertRedirectedToRoute('index');
    }

    public function testPostCreateWithdrawalEmptyBeneficiary()
    {
        // create objects.
        $account = FactoryMuffin::create('Account');
        $beneficiary = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');
        $budget = FactoryMuffin::create('Budget');


        // data to send:
        $data = [
            'beneficiary' => '',
            'category'    => $category->name,
            'budget_id'   => $budget->id,
            'account_id'  => $account->id,
            'description' => 'Bla',
            'amount'      => 1.2,
            'date'        => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('createOrFindBeneficiary')->with('')->andReturn(null);
        $accounts->shouldReceive('getCashAccount')->andReturn($beneficiary);
        $accounts->shouldReceive('find')->andReturn($account);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with($budget->name)->andReturn($budget);
        $budgets->shouldReceive('find')->andReturn($budget);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->once()->andReturn($journal);

        // call
        $this->call('POST', '/transactions/store/withdrawal', $data);

        // test
        $this->assertRedirectedToRoute('index');
    }

    public function testPostCreateDepositEmptyBeneficiary()
    {
        // create objects.
        $account = FactoryMuffin::create('Account');
        $beneficiary = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');
        $budget = FactoryMuffin::create('Budget');


        // data to send:
        $data = [
            'beneficiary' => '',
            'category'    => $category->name,
            'budget_id'   => $budget->id,
            'account_id'  => $account->id,
            'description' => 'Bla',
            'amount'      => 1.2,
            'date'        => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('createOrFindBeneficiary')->with('')->andReturn(null);
        $accounts->shouldReceive('getCashAccount')->andReturn($beneficiary);
        $accounts->shouldReceive('find')->andReturn($account);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with($budget->name)->andReturn($budget);
        $budgets->shouldReceive('find')->andReturn($budget);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->once()->andReturn($journal);

        // call
        $this->call('POST', '/transactions/store/deposit', $data);

        // test
        $this->assertRedirectedToRoute('index');
    }

    /**
     * @expectedException Firefly\Exception\FireflyException;
     */
    public function testPostCreateWithdrawalException()
    {
        // create objects.
        $account = FactoryMuffin::create('Account');
        $beneficiary = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');
        $budget = FactoryMuffin::create('Budget');


        // data to send:
        $data = [
            'beneficiary' => '',
            'category'    => $category->name,
            'budget_id'   => $budget->id,
            'account_id'  => $account->id,
            'description' => 'Bla',
            'amount'      => 1.2,
            'date'        => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('createOrFindBeneficiary')->with('')->andReturn(null);
        $accounts->shouldReceive('getCashAccount')->andReturn($beneficiary);
        $accounts->shouldReceive('find')->andReturn($account);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with($budget->name)->andReturn($budget);
        $budgets->shouldReceive('find')->andReturn($budget);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->andThrow('Firefly\Exception\FireflyException');

        // call
        $this->call('POST', '/transactions/store/withdrawal', $data);

        // test
        $this->assertRedirectedToRoute('transactions.create', ['what' => 'withdrawal']);
    }

    /**
     * @expectedException Firefly\Exception\FireflyException;
     */
    public function testPostCreateDepositException()
    {
        // create objects.
        $account = FactoryMuffin::create('Account');
        $beneficiary = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');
        $budget = FactoryMuffin::create('Budget');


        // data to send:
        $data = [
            'beneficiary' => '',
            'category'    => $category->name,
            'budget_id'   => $budget->id,
            'account_id'  => $account->id,
            'description' => 'Bla',
            'amount'      => 1.2,
            'date'        => '2012-01-01'
        ];
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('createOrFindBeneficiary')->with('')->andReturn(null);
        $accounts->shouldReceive('getCashAccount')->andReturn($beneficiary);
        $accounts->shouldReceive('find')->andReturn($account);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with($budget->name)->andReturn($budget);
        $budgets->shouldReceive('find')->andReturn($budget);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->andThrow('Firefly\Exception\FireflyException');

        // call
        $this->call('POST', '/transactions/store/deposit', $data);

        // test
        $this->assertRedirectedToRoute('transactions.create', ['what' => 'deposit']);
    }

    /**
     * @expectedException Firefly\Exception\FireflyException;
     */
    public function testPostCreateTransferException()
    {
        // create objects.
        $from = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');


        // data to send:
        $data = [
            'category'        => $category->name,
            'account_from_id' => $from->id,
            'account_to_id'   => $from->id,
            'description'     => 'Bla',
            'amount'          => 1.2,
            'date'            => '2012-01-01'
        ];

        // mock account repository:
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('find')->with($from->id)->andReturn($from);
        $accounts->shouldReceive('find')->with($from->id)->andReturn($from);

        // mock category repository
        $categories = $this->mock('Firefly\Storage\Category\CategoryRepositoryInterface');
        $categories->shouldReceive('createOrFind')->with($category->name)->andReturn($category);

        // mock budget repository
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('createOrFind')->with('')->andReturn(null);
        $budgets->shouldReceive('find')->andReturn(null);

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('createSimpleJournal')->andThrow('Firefly\Exception\FireflyException');

        // call
        $this->call('POST', '/transactions/store/transfer', $data);

        // test
        $this->assertRedirectedToRoute('transactions.create', ['what' => 'transfer']);
    }

    public function testShow()
    {
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('find')->with($journal->id)->andReturn($journal);


        // call
        $this->call('GET', '/transaction/show/' . $journal->id);

        // test
        $this->assertResponseOk();
    }

    public function testShowError()
    {

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('find')->with(1)->andReturn(null);

        // call
        $this->call('GET', '/transaction/show/1');

        // test
        $this->assertResponseOk();
        $this->assertViewHas('message');
    }

    public function testEdit()
    {
        $journal = FactoryMuffin::create('TransactionJournal');

        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('find')->with($journal->id)->andReturn($journal);

        // mock account repository
        $accounts = $this->mock('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts->shouldReceive('getActiveDefaultAsSelectList')->andReturn([]);


        // call
        $this->call('GET', '/transaction/edit/' . $journal->id);

        // test
        $this->assertResponseOk();
    }

    public function testEditError()
    {
        // mock transaction journal:
        $tj = $this->mock('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $tj->shouldReceive('find')->with(1)->andReturn(null);

        // call
        $this->call('GET', '/transaction/edit/1');

        // test
        $this->assertResponseOk();
    }

    public function tearDown()
    {
        Mockery::close();
    }
} 