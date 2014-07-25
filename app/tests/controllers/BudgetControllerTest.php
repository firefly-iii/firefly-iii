<?php


use League\FactoryMuffin\Facade\FactoryMuffin;

/**
 * Class BudgetControllerTest
 */
class BudgetControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }

    public function testIndex()
    {
        // create some objects:
        $budget = FactoryMuffin::create('Budget');
        $limit = FactoryMuffin::create('Limit');
        $rep = FactoryMuffin::create('LimitRepetition');
        $limit->limitrepetitions()->save($rep);
        $budget->limits()->save($limit);


        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('get')->once()->andReturn([$budget]);

        // call
        $this->call('GET', '/budgets/date');

        // test
        $this->assertResponseOk();
    }

    public function testIndexBudget()
    {
        // create some objects:
        $budget = FactoryMuffin::create('Budget');
        $limit = FactoryMuffin::create('Limit');
        $rep = FactoryMuffin::create('LimitRepetition');
        $limit->limitrepetitions()->save($rep);
        $budget->limits()->save($limit);


        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('get')->once()->andReturn([$budget]);

        // call
        $this->call('GET', '/budgets/budget');

        // test
        $this->assertResponseOk();
    }

    public function testCreate()
    {
        // call
        $this->call('GET', '/budget/create');

        // test
        $this->assertResponseOk();
    }

    public function testStore()
    {
        $data = [
            'name'    => 'X',
            'amount'  => 100,
            'period'  => 'monthly',
            'repeats' => 0
        ];
        $return = $data;
        $return['repeat_freq'] = 'monthly';
        unset($return['period']);

        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('store')->with($return)->once()->andReturn(true);

        // call
        $this->call('POST', '/budget/store', $data);

        // test
        $this->assertResponseStatus(302);
        $this->assertSessionHas('success');
    }

    public function testShow()
    {
        $budget = FactoryMuffin::create('Budget');
        $journal = FactoryMuffin::create('TransactionJournal');
        $transaction = FactoryMuffin::create('Transaction');
        $journal->transactions()->save($transaction);
        $budget->transactionjournals()->save($journal);


        // mock budget repository:
        $budgets = $this->mock('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets->shouldReceive('find')->with($budget->id)->once()->andReturn($budget);


        // call
        $this->call('GET', '/budget/show/' . $budget->id);

        // test
        $this->assertResponseOk();
    }

    public function tearDown()
    {
        Mockery::close();
    }


} 