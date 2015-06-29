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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @covers FireflyIII\Handlers\Events\ConnectJournalToPiggyBank::handle
     */
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


        $event  = new JournalCreated($journal, $piggyBank->id);
        $class  = new ConnectJournalToPiggyBank();
        $result = $class->handle($event);


        $this->assertFalse($result);
    }

    /**
     * @covers FireflyIII\Handlers\Events\ConnectJournalToPiggyBank::handle
     */
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

    /**
     * @covers FireflyIII\Handlers\Events\ConnectJournalToPiggyBank::handle
     */
    public function testWithRepetition()
    {
        $user = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
        FactoryMuffin::create('FireflyIII\Models\TransactionType'); // withdrawal
        FactoryMuffin::create('FireflyIII\Models\TransactionType'); // deposit

        $journal   = FactoryMuffin::create('FireflyIII\Models\TransactionJournal');
        $piggyBank = FactoryMuffin::create('FireflyIII\Models\PiggyBank');

        $journal->user_id = $user->id;
        $journal->save();

        // create piggy bank event to continue handler:
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


        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if ($transaction->amount < 0) {
                $piggyBank->account_id = $transaction->account_id;
                $account               = $transaction->account;
                $account->user_id      = $user->id;
                $account->save();
                $piggyBank->account_id = $account->id;
                $piggyBank->save();
            }
        }
        $event  = new JournalCreated($journal, $piggyBank->id);
        $class  = new ConnectJournalToPiggyBank();
        $result = $class->handle($event);

        $this->assertTrue($result);
    }
}
