<?php

use \League\FactoryMuffin\Facade\FactoryMuffin;

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
        View::shouldReceive('make')->with('transactions.withdrawal')->andReturn(\Mockery::self())
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
        $this->call('GET', '/transactions/add/withdrawal');

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

//        $tj->shouldReceive('createSimpleJournal')->with($account, $beneficiary, $data['description'], $data['amount'], new \Carbon\Carbon($data['date']))->once()->andReturn($journal);

        // call
        $this->call('POST', '/transactions/add/withdrawal', $data);

        // test
        $this->assertRedirectedToRoute('index');
    }
    public function tearDown()
    {
        Mockery::close();
    }
} 