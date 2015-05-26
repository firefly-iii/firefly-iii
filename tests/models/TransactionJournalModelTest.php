<?php

use FireflyIII\Models\Transaction;
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

        Transaction::create(
            [
                'account_id'             => $account->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 123.45
            ]
        );
        $amount = $journal->actual_amount;
        $this->assertEquals('123.45', $amount);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetAmountAttributeAdvancePayment()
    {
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

        // for withdrawal, asset to expense account and reversed: //89,88
        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => -300]);
        Transaction::create(['account_id' => $expense->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => 300]);

        Transaction::create(['account_id' => $revenue->id, 'transaction_journal_id' => $deposit->id, 'amount' => -89.88]);
        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $deposit->id, 'amount' => 89.88]);


        // connect to tag:
        $tag->transactionJournals()->save($withdrawal);
        $tag->transactionJournals()->save($deposit);

        // amount should be 210.12:
        $this->assertEquals('210.12', $withdrawal->amount);
        $this->assertEquals('0', $deposit->amount);


    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetAmountAttributeBalancingAct()
    {
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
        $expense                 = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset2                  = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset                   = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset2->account_type_id = $asset->account_type_id;
        $asset2->save();

        // make transactions:
        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => -123.45]);
        Transaction::create(['account_id' => $expense->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => 123.45]);

        Transaction::create(['account_id' => $asset2->id, 'transaction_journal_id' => $transfer->id, 'amount' => -123.45]);
        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $transfer->id, 'amount' => 123.45]);

        $amount = $withdrawal->amount;

        $this->assertEquals('0', $amount);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     */
    public function testGetAmountAttributeNoTags()
    {
        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $account = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(
            [
                'account_id'             => $account->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 123.45
            ]
        );
        $amount = $journal->amount;
        $this->assertEquals('123.45', $amount);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAmountAttribute
     */
    public function testGetAmountAttributeTag()
    {
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

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => -300]);
        Transaction::create(['account_id' => $expense->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => 300]);

        // connect to tag:
        $tag->transactionJournals()->save($withdrawal);

        $this->assertEquals('300', $withdrawal->amount);


    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAssetAccountAttribute
     */
    public function testGetAssetAccountAttributeDeposit()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');

        // make withdrawal
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $deposit->id, 'amount' => 300]);
        Transaction::create(['account_id' => $revenue->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);

        // get asset account:
        $result = $deposit->asset_account;

        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAssetAccountAttribute
     */
    public function testGetAssetAccountAttributeFallback()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');

        // make withdrawal
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);
        Transaction::create(['account_id' => $revenue->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);

        // get asset account:
        $result = $deposit->asset_account;

        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getAssetAccountAttribute
     */
    public function testGetAssetAccountAttributeWithdrawal()
    {
        // make withdrawal
        $withdrawalType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $withdrawal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $withdrawal->transaction_type_id = $withdrawalType->id;
        $withdrawal->save();

        // make accounts
        $expense = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => -300]);
        Transaction::create(['account_id' => $expense->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => 300]);

        // get asset account:
        $result = $withdrawal->asset_account;

        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getCorrectedActualAmountAttribute
     */
    public function testGetCorrectedActualAmountAttributeDeposit()
    {

        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $deposit->id, 'amount' => 300]);
        Transaction::create(['account_id' => $revenue->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);

        // get asset account:
        $result = $deposit->corrected_actual_amount;

        $this->assertEquals('300', $result);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getCorrectedActualAmountAttribute
     */
    public function testGetCorrectedActualAmountAttributeWithdrawal()
    {

        // make withdrawal
        $withdrawalType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $withdrawal                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $withdrawal->transaction_type_id = $withdrawalType->id;
        $withdrawal->save();

        // make accounts
        $expense = FactoryMuffin::create('FireflyIII\Models\Account');
        FactoryMuffin::create('FireflyIII\Models\Account');
        $asset = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $expense->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => 300]);
        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $withdrawal->id, 'amount' => -300]);

        // get asset account:
        $result = $withdrawal->corrected_actual_amount;

        $this->assertEquals('-300', $result);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getDestinationAccountAttribute
     */
    public function testGetDestinationAccountAttribute()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $deposit->id, 'amount' => 300]);
        Transaction::create(['account_id' => $revenue->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);

        // get asset account:
        $result = $deposit->destination_account;

        $this->assertEquals($asset->id, $result->id);
    }

    /**
     * @covers FireflyIII\Models\TransactionJournal::getDestinationAccountAttribute
     */
    public function testGetDestinationAccountAttributeFallback()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $depositType                  = FactoryMuffin::create('FireflyIII\Models\TransactionType');
        $deposit                      = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $deposit->transaction_type_id = $depositType->id;
        $deposit->save();

        // make accounts
        FactoryMuffin::create('FireflyIII\Models\Account');
        $revenue = FactoryMuffin::create('FireflyIII\Models\Account');
        $asset   = FactoryMuffin::create('FireflyIII\Models\Account');

        Transaction::create(['account_id' => $asset->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);
        Transaction::create(['account_id' => $revenue->id, 'transaction_journal_id' => $deposit->id, 'amount' => -300]);

        // get asset account:
        $result = $deposit->destination_account;

        $this->assertEquals($asset->id, $result->id);
    }

}
