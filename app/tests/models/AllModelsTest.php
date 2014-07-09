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

        $this->assertEquals($tj->transaction_type_id,$tj->transactionType()->first()->id);
        $this->assertEquals($tj->transaction_currency_id,$tj->transactionCurrency()->first()->id);

    }

    public function testTransactions() {
        $transaction = FactoryMuff::create('Transaction');

        $budget = FactoryMuff::create('Budget');
        $category = FactoryMuff::create('Category');

        $transaction->components()->save($budget);
        $transaction->components()->save($category);

        $this->assertCount(2,$transaction->components()->get());
        $this->assertCount(1,$transaction->budgets()->get());
        $this->assertCount(1,$transaction->categories()->get());


    }
} 