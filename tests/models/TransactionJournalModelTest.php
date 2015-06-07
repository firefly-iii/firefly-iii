<?php

use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class TransactionJournalModelTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class TransactionJournalModelTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();

    }

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getActualAmountAttribute
     */
    public function testGetActualAmountAttribute()
    {
        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $account = FactoryMuffin::create('FireflyIII\Models\Account');

        $journal->transactions[0]->amount = '123.45';
        $journal->transactions[0]->save();
        $journal->transactions[1]->amount = '-123.45';
        $journal->transactions[1]->save();

        $amount = $journal->actual_amount;
        $this->assertEquals('123.45', $amount);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @covers FireflyIII\Models\TransactionJournal::amountByTag
     * @covers FireflyIII\Models\TransactionJournal::amountByTags
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetAmountAttributeAdvancePayment()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        // make types:
        $withdrawalType = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $depositType    = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        // make tag
        $tag          = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag->tagMode = 'advancePayment';
        $tag->save();

        // make withdrawal
        $withdrawal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $withdrawal->transaction_type_id = $withdrawalType->id;
        $withdrawal->save();

        // make deposit
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        $expense = FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        // transactions are already in place, update them:
        $withdrawal->transactions[0]->account_id = $asset->id;
        $withdrawal->transactions[0]->amount     = -300;
        $withdrawal->transactions[0]->save();

        $withdrawal->transactions[1]->account_id = $expense->id;
        $withdrawal->transactions[1]->amount     = 300;
        $withdrawal->transactions[1]->save();

        $deposit->transactions[0]->account_id = $revenue->id;
        $deposit->transactions[0]->amount     = -89.88;
        $deposit->transactions[0]->save();

        $deposit->transactions[1]->account_id = $asset->id;
        $deposit->transactions[1]->amount     = 89.88;
        $deposit->transactions[1]->save();

        // connect to tag:
        $tag->transactionJournals()->save($withdrawal);
        $tag->transactionJournals()->save($deposit);

        // amount should be 210.12:
        $this->assertEquals('210.12', $withdrawal->amount);
        $this->assertEquals('0', $deposit->amount);


    }


    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @covers FireflyIII\Models\TransactionJournal::amountByTag
     * @covers FireflyIII\Models\TransactionJournal::amountByTags
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetAmountAttributeBalancingAct()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // make types:
        $withdrawalType = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $transferType = FactoryMuffin::create('FireflyIII\Models\TransactionType');

        // make a tag
        $tag          = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag->tagMode = 'balancingAct';
        $tag->save();

        // make a withdrawal and a transfer
        $withdrawal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $withdrawal->transaction_type_id = $withdrawalType->id;
        $withdrawal->save();

        $transfer                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $transfer->transaction_type_id = $transferType->id;
        $transfer->save();

        // connect to tag:
        $tag->transactionJournals()->save($withdrawal);
        $tag->transactionJournals()->save($transfer);

        // make accounts:
        $expense                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset                    = FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue->account_type_id = $asset->account_type_id;
        $revenue->save();

        // transactions are already in place, update them:
        $withdrawal->transactions[0]->account_id = $asset->id;
        $withdrawal->transactions[0]->amount     = -123.45;
        $withdrawal->transactions[0]->save();

        $withdrawal->transactions[1]->account_id = $expense->id;
        $withdrawal->transactions[1]->amount     = 123.45;
        $withdrawal->transactions[1]->save();

        $transfer->transactions[0]->account_id = $revenue->id;
        $transfer->transactions[0]->amount     = -123.45;
        $transfer->transactions[0]->save();

        $transfer->transactions[1]->account_id = $asset->id;
        $transfer->transactions[1]->amount     = 123.45;
        $transfer->transactions[1]->save();

        $amount = $withdrawal->amount;

        $this->assertEquals('0', $amount);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @covers FireflyIII\Models\TransactionJournal::amountByTag
     * @covers FireflyIII\Models\TransactionJournal::amountByTags
     */
    public function testGetAmountAttributeNoTags()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');

        $journal->transactions[0]->amount = 123.45;
        $journal->transactions[0]->save();

        $journal->transactions[1]->amount = -123.45;
        $journal->transactions[1]->save();

        $amount = $journal->amount;
        $this->assertEquals('123.45', $amount);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @covers FireflyIII\Models\TransactionJournal::amountByTag
     * @covers FireflyIII\Models\TransactionJournal::amountByTags
     */
    public function testGetAmountAttributeTag()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // has a normal tag, but nothing special.
        // make tag
        $tag          = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag->tagMode = 'nothing';
        $tag->save();

        // make withdrawal
        $withdrawalType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $withdrawal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $withdrawal->transaction_type_id = $withdrawalType->id;
        $withdrawal->save();

        // make accounts
        $expense = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        $withdrawal->transactions[0]->amount     = -300;
        $withdrawal->transactions[0]->account_id = $asset->id;
        $withdrawal->transactions[0]->save();

        $withdrawal->transactions[1]->amount     = 300;
        $withdrawal->transactions[1]->account_id = $expense->id;
        $withdrawal->transactions[1]->save();

        // connect to tag:
        $tag->transactionJournals()->save($withdrawal);

        $this->assertEquals('300', $withdrawal->amount);


    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @covers FireflyIII\Models\TransactionJournal::amountByTag
     * @covers FireflyIII\Models\TransactionJournal::amountByTags
     */
    public function testGetAmountAttributeTags()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        // has two normal tags:
        $tag          = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag->tagMode = 'nothing';
        $tag->save();
        $tag2          = FactoryMuffin::create('FireflyIII\Models\Tag');
        $tag2->tagMode = 'nothing';
        $tag2->save();

        // make withdrawal
        $withdrawalType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $withdrawal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $withdrawal->transaction_type_id = $withdrawalType->id;
        $withdrawal->save();

        // make accounts
        $expense = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        $withdrawal->transactions[0]->amount     = -300;
        $withdrawal->transactions[0]->account_id = $asset->id;
        $withdrawal->transactions[0]->save();

        $withdrawal->transactions[1]->amount     = 300;
        $withdrawal->transactions[1]->account_id = $expense->id;
        $withdrawal->transactions[1]->save();

        // connect to tag:
        $tag->transactionJournals()->save($withdrawal);
        $tag2->transactionJournals()->save($withdrawal);

        $this->assertEquals('300', $withdrawal->amount);


    }


    /**
     * @covers FireflyIII\Models\TransactionJournal::getCorrectAmountAttribute
     */
    public function testGetCorrectAmountAttribute()
    {
        $withdrawal = FactoryMuffin::create('FireflyIII\Models\TransactionType'); // withdrawal

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        // make withdrawal
        $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journal->transaction_type_id = $withdrawal->id;
        $journal->save();

        $journal->transactions[0]->account_id = $asset->id;
        $journal->transactions[0]->amount     = 300;
        $journal->transactions[0]->save();

        $journal->transactions[1]->account_id = $revenue->id;
        $journal->transactions[1]->amount     = -300;
        $journal->transactions[1]->save();

        // get asset account:
        $result = $journal->correct_amount;

        $this->assertEquals(-300, $result);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getCorrectAmountAttribute
     */
    public function testGetCorrectAmountAttributeDeposit()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType'); // withdrawal
        $deposit = FactoryMuffin::create('FireflyIII\Models\TransactionType'); // deposit

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        // make withdrawal
        $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journal->transaction_type_id = $deposit->id;
        $journal->save();

        $journal->transactions[0]->account_id = $asset->id;
        $journal->transactions[0]->amount     = 300;
        $journal->transactions[0]->save();

        $journal->transactions[1]->account_id = $revenue->id;
        $journal->transactions[1]->amount     = -300;
        $journal->transactions[1]->save();

        // get asset account:
        $result = $journal->correct_amount;

        $this->assertEquals(300, $result);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getCorrectAmountAttribute
     */
    public function testGetCorrectAmountAttributeTransfer()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType'); // withdrawal
        FactoryMuffin::create('FireflyIII\Models\TransactionType'); // deposit
        $transfer = FactoryMuffin::create('FireflyIII\Models\TransactionType'); // transfer

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        // make withdrawal
        $journal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $journal->transaction_type_id = $transfer->id;
        $journal->save();

        $journal->transactions[0]->account_id = $asset->id;
        $journal->transactions[0]->amount     = 300;
        $journal->transactions[0]->save();

        $journal->transactions[1]->account_id = $revenue->id;
        $journal->transactions[1]->amount     = -300;
        $journal->transactions[1]->save();

        // get asset account:
        $result = $journal->correct_amount;

        $this->assertEquals('300', $result);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getDestinationAccountAttribute
     */
    public function testGetDestinationAccountAttribute()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        $deposit->transactions[0]->account_id = $asset->id;
        $deposit->transactions[0]->amount     = 300;
        $deposit->transactions[0]->save();

        $deposit->transactions[1]->account_id = $revenue->id;
        $deposit->transactions[1]->amount     = -300;
        $deposit->transactions[1]->save();

        // get asset account:
        $result = $deposit->destination_account;

        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getSourceAccountAttribute
     */
    public function testGetSourceAccountAttribute()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        $deposit->transactions[0]->account_id = $asset->id;
        $deposit->transactions[0]->amount     = 300;
        $deposit->transactions[0]->save();

        $deposit->transactions[1]->account_id = $revenue->id;
        $deposit->transactions[1]->amount     = -300;
        $deposit->transactions[1]->save();

        // get asset account:
        $result = $deposit->source_account;

        $this->assertEquals($revenue->id, $result->id);
    }

}
