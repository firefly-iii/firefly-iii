<?php

use Zizaco\FactoryMuff\Facade\FactoryMuff;

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
        $user = FactoryMuff::create('User');
        $pref = FactoryMuff::create('Preference');
        $account = FactoryMuff::create('Account');

        $account->user()->associate($user);
        $pref->user()->associate($user);
        $user->accounts()->save($account);
        $user->preferences()->save($pref);

        $this->assertEquals($account->user_id, $user->id);
        $this->assertEquals($pref->user_id,$user->id);

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

    public function testTransactionJournals() {
        $tj = FactoryMuff::create('TransactionJournal');

        $t1 = FactoryMuff::create('Transaction');
        $t2 = FactoryMuff::create('Transaction');
        $t3 = FactoryMuff::create('Transaction');

        $tj->transactions()->save($t1);
        $tj->transactions()->save($t2);
        $tj->transactions()->save($t3);

        $budget = FactoryMuff::create('Budget');
        $category = FactoryMuff::create('Category');

        $tj->components()->save($budget);
        $tj->components()->save($category);

        $this->assertCount(2,$tj->components()->get());
        $this->assertCount(1,$tj->budgets()->get());
        $this->assertCount(1,$tj->categories()->get());

        $this->assertCount(3,$tj->transactions()->get());

        $this->assertTrue($tj->isValid());

        $this->assertEquals($tj->transaction_type_id,$tj->transactionType()->first()->id);
        $this->assertEquals($tj->transaction_currency_id,$tj->transactionCurrency()->first()->id);

    }

    public function testTransactionJournalScope() {
        $tj = FactoryMuff::create('TransactionJournal');
        $tj->date = new \Carbon\Carbon('2012-01-02');

        $set = $tj->after(new \Carbon\Carbon)->before(new \Carbon\Carbon)->get();
        $this->assertCount(0,$set);
    }

    public function testTransactionType() {
        $j1 = FactoryMuff::create('TransactionJournal');
        $j2 = FactoryMuff::create('TransactionJournal');

        $type = FactoryMuff::create('TransactionType');
        $type->transactionjournals()->save($j1);
        $type->transactionjournals()->save($j2);

        $this->assertCount(2,$type->transactionjournals()->get());

    }

    public function testTransactionCurrency() {
        $j1 = FactoryMuff::create('TransactionJournal');
        $j2 = FactoryMuff::create('TransactionJournal');

        $currency = FactoryMuff::create('TransactionCurrency');
        $currency->transactionjournals()->save($j1);
        $currency->transactionjournals()->save($j2);

        $this->assertCount(2,$currency->transactionjournals()->get());

    }

    public function testAccountTypes() {
        $type = FactoryMuff::create('AccountType');
        $a1 = FactoryMuff::create('Account');
        $a2 = FactoryMuff::create('Account');

        $type->accounts()->save($a1);
        $type->accounts()->save($a2);

        $this->assertCount(2,$type->accounts()->get());
    }

    public function testTransactions() {
        $transaction = FactoryMuff::create('Transaction');

        $budget = FactoryMuff::create('Budget');
        $account = FactoryMuff::create('Account');
        $category = FactoryMuff::create('Category');
        $journal = FactoryMuff::create('TransactionJournal');

        $transaction->components()->save($budget);
        $transaction->components()->save($category);
        $transaction->account()->associate($account);
        $transaction->transactionjournal()->associate($journal);

        $this->assertCount(1,$transaction->transactionjournal()->get());
        $this->assertCount(1,$transaction->account()->get());
        $this->assertCount(2,$transaction->components()->get());
        $this->assertCount(1,$transaction->budgets()->get());
        $this->assertCount(1,$transaction->categories()->get());

    }

    public function testComponents() {
        $component = FactoryMuff::create('Component');
        $user = FactoryMuff::create('User');
        $transaction = FactoryMuff::create('Transaction');

        $journal = FactoryMuff::create('TransactionJournal');
        $component->transactionjournals()->save($journal);
        $component->user()->associate($user);
        $component->transactions()->save($transaction);

        $this->assertCount(1,$component->transactionjournals()->get());
        $this->assertCount(1,$component->user()->get());
        $this->assertCount(1,$component->transactions()->get());
    }
} 