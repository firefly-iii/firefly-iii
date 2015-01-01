<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class TransactionTest
 */
class TransactionTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testAccount()
    {
        $transaction = f::create('Transaction');

        $this->assertCount(1, Transaction::accountIs($transaction->account)->get());
    }

    public function testDateAfter()
    {
        $transaction = f::create('Transaction');
        $date        = clone $transaction->transactionJournal->date;
        $date->subDay();

        $this->assertCount(1, Transaction::after($date)->get());
    }

    public function testDateBefore()
    {
        $transaction = f::create('Transaction');
        $date        = clone $transaction->transactionJournal->date;
        $date->addDay();

        $this->assertCount(1, Transaction::before($date)->get());
    }

    public function testLessThan()
    {
        $transaction = f::create('Transaction');
        $amount      = floatval($transaction->amount);
        $amount++;
        $this->assertCount(1, Transaction::lessThan($amount)->get());
    }

    public function testMoreThan()
    {
        $transaction = f::create('Transaction');
        $amount      = floatval($transaction->amount);
        $amount--;
        $this->assertCount(1, Transaction::moreThan($amount)->get());
    }

    public function testTransactionTypes()
    {
        $transaction = f::create('Transaction');
        $type        = $transaction->transactionJournal->transactionType->type;
        $this->assertCount(1, Transaction::transactionTypes([$type])->get());
    }
}