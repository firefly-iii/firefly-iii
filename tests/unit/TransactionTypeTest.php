<?php
use League\FactoryMuffin\Facade as f;

/**
 * Class TransactionTypeTest
 */
class TransactionTypeTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    // tests

    public function testJournals()
    {
        $journal = f::create('TransactionJournal');
        $type = $journal->transactionType;
        $this->assertCount(1, $type->transactionJournals()->get());
    }


}