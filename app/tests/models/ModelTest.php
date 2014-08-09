<?php
use Carbon\Carbon;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

class ModelTest extends TestCase
{


    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testAccount()
    {
        $account = f::create('Account');
        $user = f::create('User');
        $type = f::create('AccountType');
        $account->user()->associate($user);
        $account->accounttype()->associate($type);

        $this->assertEquals($account->predict(new Carbon), null);
        $this->assertEquals($account->balance(new Carbon), null);
        $this->assertEquals($account->user_id, $user->id);
        $this->assertEquals($account->account_type_id, $type->id);

    }

    public function testAccountType()
    {
        $account = f::create('Account');
        $type = f::create('AccountType');
        $type->accounts()->save($account);

        $this->assertEquals($account->account_type_id, $type->id);
    }

    public function testBudget()
    {
        $budget = f::create('Budget');
        $limit = f::create('Limit');
        $journal = f::create('TransactionJournal');
        $budget->limits()->save($limit);
        $budget->transactionjournals()->save($journal);

        $this->assertEquals($limit->component_id, $budget->id);
        $this->assertEquals($journal->budgets()->first()->id, $budget->id);
    }

    public function testCategory()
    {
        $category = f::create('Category');
        $journal = f::create('TransactionJournal');
        $category->transactionjournals()->save($journal);

        $this->assertEquals($journal->categories()->first()->id, $category->id);
    }

    public function testComponent()
    {
        $component = f::create('Component');
        $limit = f::create('Limit');
        $component->limits()->save($limit);
        $component->save();

        $transaction = f::create('Transaction');
        $journal = f::create('TransactionJournal');
        $user = f::create('User');


        $component->transactions()->save($transaction);
        $component->transactionjournals()->save($journal);
        $component->user()->associate($user);

        $this->assertEquals($transaction->components()->first()->id, $component->id);
        $this->assertEquals($journal->components()->first()->id, $component->id);
        $this->assertEquals($limit->component()->first()->id, $component->id);
        $this->assertEquals($component->user_id, $user->id);

    }

    public function testLimit()
    {
        $limit = f::create('Limit');
        $budget = f::create('Budget');
        $rep = f::create('LimitRepetition');
        $limit->budget()->associate($budget);
        $limit->limitrepetitions()->save($rep);
        $rep->save();
        $limit->save();

        $this->assertEquals($rep->limit_id,$limit->id);
        $this->assertEquals($limit->component_id, $budget->id);

        // create repetition:
        $start = new Carbon;
        $list = ['daily','weekly','monthly','quarterly','half-year','yearly'];
        foreach($list as $entry) {
            $limit->repeat_freq = $entry;
            $limit->createRepetition($start);
        }

        // try and fail:


    }

}