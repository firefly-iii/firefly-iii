<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class TransactionJournalTest
 */
class TransactionJournalTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testMorethan()
    {
        $journal = f::create('TransactionJournal');
        $transaction = f::create('Transaction');
        $other = clone $transaction;
        $journal->transactions()->save($transaction);
        $journal->transactions()->save($other);

        $amount      = floatval($transaction->amount);
        $amount--;

        $this->assertCount(1, TransactionJournal::moreThan($amount)->get());
    }
}
