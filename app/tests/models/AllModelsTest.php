<?php

use League\FactoryMuffin\Facade\FactoryMuffin;

class AllModelsTest extends TestCase
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


    /**
     * User tests
     */
    public function testUser()
    {
        $user = FactoryMuffin::create('User');
        $pref = FactoryMuffin::create('Preference');
        $account = FactoryMuffin::create('Account');

        // some more stuff:
        $component = FactoryMuffin::create('Component');
        $budget = FactoryMuffin::create('Budget');
        $category = FactoryMuffin::create('Category');

        $account->user()->associate($user);
        $pref->user()->associate($user);
        $user->accounts()->save($account);
        $user->preferences()->save($pref);
        $user->components()->save($component);
        $user->budgets()->save($budget);
        $user->categories()->save($category);

        $this->assertEquals($account->user_id, $user->id);
        $this->assertEquals($pref->user_id, $user->id);
        $this->assertEquals($budget->user_id, $user->id);
        $this->assertEquals($category->user_id, $user->id);
        $this->assertEquals($component->user_id, $user->id);

        // test pref?
        $pref->data = 'Blabla';
        $this->assertEquals($pref->data, 'Blabla');

        $this->assertCount(1, $user->accounts()->get());
        $this->assertCount(1, $user->preferences()->get());


        $this->assertTrue(true);

    }

    /**
     * Account tests
     */
    public function testUserAccounts()
    {

        $this->assertTrue(true);

    }

    /**
     * Transaction journal tests.
     */

    public function testTransactionJournals()
    {
        $tj = FactoryMuffin::create('TransactionJournal');

        $t1 = FactoryMuffin::create('Transaction');
        $t2 = FactoryMuffin::create('Transaction');
        $t3 = FactoryMuffin::create('Transaction');
        $user = FactoryMuffin::create('User');

        $tj->transactions()->save($t1);
        $tj->transactions()->save($t2);
        $tj->transactions()->save($t3);

        $budget = FactoryMuffin::create('Budget');
        $category = FactoryMuffin::create('Category');

        $tj->components()->save($budget);
        $tj->components()->save($category);
        $user->transactionjournals()->save($tj);

        $this->assertCount(2, $tj->components()->get());
        $this->assertCount(1, $tj->budgets()->get());
        $this->assertCount(1, $tj->categories()->get());
        $this->assertCount(1, $user->transactionjournals()->get());


        $this->assertCount(3, $tj->transactions()->get());

        $this->assertTrue($tj->validate());

        $this->assertEquals($tj->transaction_type_id, $tj->transactionType()->first()->id);
        $this->assertEquals($tj->transaction_currency_id, $tj->transactionCurrency()->first()->id);

    }

    public function testTransactionJournalScope()
    {
        $tj = FactoryMuffin::create('TransactionJournal');
        $tj->date = new \Carbon\Carbon('2012-01-02');

        $set = $tj->after(new \Carbon\Carbon)->before(new \Carbon\Carbon)->get();
        $this->assertCount(0, $set);
    }

    public function testTransactionType()
    {
        $j1 = FactoryMuffin::create('TransactionJournal');
        $j2 = FactoryMuffin::create('TransactionJournal');

        $type = FactoryMuffin::create('TransactionType');
        $type->transactionjournals()->save($j1);
        $type->transactionjournals()->save($j2);

        $this->assertCount(2, $type->transactionjournals()->get());

    }

    public function testTransactionCurrency()
    {
        $j1 = FactoryMuffin::create('TransactionJournal');
        $j2 = FactoryMuffin::create('TransactionJournal');

        $currency = FactoryMuffin::create('TransactionCurrency');
        $currency->transactionjournals()->save($j1);
        $currency->transactionjournals()->save($j2);

        $this->assertCount(2, $currency->transactionjournals()->get());

    }

    public function testAccountTypes()
    {
        $type = FactoryMuffin::create('AccountType');
        $a1 = FactoryMuffin::create('Account');
        $a2 = FactoryMuffin::create('Account');

        $type->accounts()->save($a1);
        $type->accounts()->save($a2);
        $a1->accounttype()->associate($type);

        $this->assertEquals($a1->account_type_id, $type->id);

        $this->assertCount(2, $type->accounts()->get());
    }

    public function testBalance()
    {
        $account = FactoryMuffin::create('Account');

        $this->assertEquals(0.0, $account->balance());

    }

    public function testTransactions()
    {
        $transaction = FactoryMuffin::create('Transaction');

        $budget = FactoryMuffin::create('Budget');
        $account = FactoryMuffin::create('Account');
        $category = FactoryMuffin::create('Category');
        $journal = FactoryMuffin::create('TransactionJournal');

        $transaction->components()->save($budget);
        $transaction->components()->save($category);
        $transaction->account()->associate($account);
        $transaction->transactionjournal()->associate($journal);

        $account->transactions()->save($transaction);


        $this->assertEquals($transaction->account_id, $account->id);
        $this->assertCount(1, $transaction->transactionjournal()->get());
        $this->assertCount(1, $transaction->account()->get());
        $this->assertCount(2, $transaction->components()->get());
        $this->assertCount(1, $transaction->budgets()->get());
        $this->assertCount(1, $transaction->categories()->get());

    }

    public function testComponents()
    {
        $component = FactoryMuffin::create('Component');
        $user = FactoryMuffin::create('User');
        $transaction = FactoryMuffin::create('Transaction');

        $journal = FactoryMuffin::create('TransactionJournal');
        $component->transactionjournals()->save($journal);
        $component->user()->associate($user);
        $component->transactions()->save($transaction);

        $this->assertCount(1, $component->transactionjournals()->get());
        $this->assertCount(1, $component->user()->get());
        $this->assertCount(1, $component->transactions()->get());
    }

    public function tearDown()
    {
        Mockery::close();
    }
}