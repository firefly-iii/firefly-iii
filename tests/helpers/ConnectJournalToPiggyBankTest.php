<?php
use FireflyIII\Events\JournalCreated;
use FireflyIII\Handlers\Events\ConnectJournalToPiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ConnectJournalToPiggyBankTest
 */
class ConnectJournalToPiggyBankTest extends TestCase
{
    //event(new JournalCreated($journal, intval($request->get('piggy_bank_id'))));

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testNoRepetition()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        /** @var \FireflyIII\Models\PiggyBank $piggyBank */
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $account1              = FactoryMuffin::create('FireflyIII\Models\Account');
        $account2              = FactoryMuffin::create('FireflyIII\Models\Account');
        $account1->user_id     = $user->id;
        $account2->user_id     = $user->id;
        $piggyBank->account_id = $account1->id;
        $account1->save();
        $account2->save();
        $piggyBank->save();

        // because the event handler responds to this piggy bank, we must remove
        // the piggy bank repetition:
        /** @var \FireflyIII\Models\PiggyBankRepetition $rep */
        foreach ($piggyBank->piggyBankRepetitions()->get() as $rep) {
            $rep->forceDelete();
        }

        Transaction::create(
            [
                'account_id'             => $account1->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 100
            ]
        );
        Transaction::create(
            [
                'account_id'             => $account2->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => -100
            ]
        );

        // two transactions:


        $event  = new JournalCreated($journal, $piggyBank->id);
        $class  = new ConnectJournalToPiggyBank();
        $result = $class->handle($event);


        $this->assertFalse($result);
    }

    public function testNoSuchPiggy()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $journal = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $event   = new JournalCreated($journal, 1);
        $class   = new ConnectJournalToPiggyBank();
        $result  = $class->handle($event);


        $this->assertFalse($result);
    }

    public function testWithRepetition()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $journal               = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $account1              = FactoryMuffin::create('FireflyIII\Models\Account');
        $account2              = FactoryMuffin::create('FireflyIII\Models\Account');
        $account1->user_id     = $user->id;
        $account2->user_id     = $user->id;
        $piggyBank->account_id = $account1->id;
        $account1->save();
        $account2->save();
        $piggyBank->save();

        $start = clone $journal->date;
        $end   = clone $journal->date;
        $start->subDay();
        $end->addDay();

        PiggyBankRepetition::create(
            [
                'piggy_bank_id' => $piggyBank->id,
                'startdate'     => $start->format('Y-m-d'),
                'targetdate'    => $end->format('Y-m-d'),
                'currentamount' => 0,
            ]
        );

        Transaction::create(
            [
                'account_id'             => $account1->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 100
            ]
        );
        Transaction::create(
            [
                'account_id'             => $account2->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => -100
            ]
        );

        $event  = new JournalCreated($journal, $piggyBank->id);
        $class  = new ConnectJournalToPiggyBank();
        $result = $class->handle($event);


        $this->assertTrue($result);
        $this->assertCount(1, $piggyBank->piggyBankEvents()->get());
    }

    public function testWithRepetitionReversed()
    {
        FactoryMuffin::create('FireflyIII\Models\TransactionType');
        FactoryMuffin::create('FireflyIII\Models\TransactionType');

        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);

        $journal               = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $piggyBank             = FactoryMuffin::create('FireflyIII\Models\PiggyBank');
        $account1              = FactoryMuffin::create('FireflyIII\Models\Account');
        $account2              = FactoryMuffin::create('FireflyIII\Models\Account');
        $account1->user_id     = $user->id;
        $account2->user_id     = $user->id;
        $piggyBank->account_id = $account1->id;
        $account1->save();
        $account2->save();
        $piggyBank->save();

        $start = clone $journal->date;
        $end   = clone $journal->date;
        $start->subDay();
        $end->addDay();

        PiggyBankRepetition::create(
            [
                'piggy_bank_id' => $piggyBank->id,
                'startdate'     => $start->format('Y-m-d'),
                'targetdate'    => $end->format('Y-m-d'),
                'currentamount' => 0,
            ]
        );

        Transaction::create(
            [
                'account_id'             => $account1->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => -100
            ]
        );
        Transaction::create(
            [
                'account_id'             => $account2->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => 100
            ]
        );

        $event  = new JournalCreated($journal, $piggyBank->id);
        $class  = new ConnectJournalToPiggyBank();
        $result = $class->handle($event);


        $this->assertTrue($result);
        $this->assertCount(1, $piggyBank->piggyBankEvents()->get());
    }

}