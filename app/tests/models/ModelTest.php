<?php
use Carbon\Carbon;
use Zizaco\FactoryMuff\Facade\FactoryMuff as f;

class ModelTest extends TestCase
{


    public function setUp()
    {
        parent::setUp();
        Artisan::call('migrate');
        Artisan::call('db:seed');

    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testAccount()
    {
        $account = f::create('Account');
        $user = f::create('User');
        $type = f::create('AccountType');
        $account->user()->associate($user);
        $account->accounttype()->associate($type);

        $this->assertEquals($account->predict(new Carbon), null);
        $this->assertEquals($account->balance(new Carbon), null);
        $this->assertEquals($account->user_id, $user->id);
        $this->assertEquals($account->account_type_id, $type->id);

    }

    public function testAccountType()
    {
        $account = f::create('Account');
        $type = f::create('AccountType');
        $type->accounts()->save($account);

        $this->assertEquals($account->account_type_id, $type->id);
    }

    public function testBudget()
    {
        $budget = f::create('Budget');
        $limit = f::create('Limit');
        $journal = f::create('TransactionJournal');
        $budget->limits()->save($limit);
        $budget->transactionjournals()->save($journal);

        $this->assertEquals($limit->component_id, $budget->id);
        $this->assertEquals($journal->budgets()->first()->id, $budget->id);
    }

    public function testCategory()
    {
        $category = f::create('Category');
        $journal = f::create('TransactionJournal');
        $category->transactionjournals()->save($journal);

        $this->assertEquals($journal->categories()->first()->id, $category->id);
    }

    public function testComponent()
    {
        $component = f::create('Component');
        $limit = f::create('Limit');
        $component->limits()->save($limit);
        $component->save();

        $transaction = f::create('Transaction');
        $journal = f::create('TransactionJournal');
        $user = f::create('User');


        $component->transactions()->save($transaction);
        $component->transactionjournals()->save($journal);
        $component->user()->associate($user);

        $this->assertEquals($transaction->components()->first()->id, $component->id);
        $this->assertEquals($journal->components()->first()->id, $component->id);
        $this->assertEquals($limit->component()->first()->id, $component->id);
        $this->assertEquals($component->user_id, $user->id);

    }

    public function testLimit()
    {
        $limit = f::create('Limit');
        $budget = f::create('Budget');
        $rep = f::create('LimitRepetition');
        $limit->budget()->associate($budget);
        $limit->limitrepetitions()->save($rep);
        $rep->save();
        $limit->save();

        $this->assertEquals($rep->limit_id, $limit->id);
        $this->assertEquals($limit->component_id, $budget->id);

        // create repetition:
        $start = new Carbon;
        $list = ['daily', 'weekly', 'monthly', 'quarterly', 'half-year', 'yearly'];
        foreach ($list as $entry) {
            $limit->repeat_freq = $entry;
            $limit->createRepetition($start);
        }

    }

    /**
     * @expectedException \Firefly\Exception\FireflyException
     */
    public function testLimitrepetition()
    {
        $limit = f::create('Limit');
        $rep = f::create('LimitRepetition');
        $budget = f::create('Budget');
        $journal = f::create('TransactionJournal');
        $one = f::create('Transaction');
        $two = f::create('Transaction');
        $one->amount = 300;
        $two->amount = -300;

        $rep->limit()->associate($limit);
        $limit->budget()->associate($budget);
        $journal->transactions()->save($one);
        $journal->transactions()->save($two);
        $journal->budgets()->save($budget);

        $this->assertEquals(($rep->amount - 300), $rep->left());

        // repeat frequency (not present) for periodOrder
        $testDate = new Carbon;
        $testDate->startOfMonth();
        $rep->repeat_freq = null;
        $this->assertEquals($testDate->format('Ymd') . '-3', $rep->periodOrder());

        // repeat frequency (present) for periodOrder
        $list = ['yearly', 'half-year', 'quarterly', 'monthly', 'weekly', 'daily'];
        foreach ($list as $index => $entry) {
            $rep->repeat_freq = $entry;
            $this->assertEquals($testDate->format('Ymd') . '-' . $index, $rep->periodOrder());
        }

        // repeat freq (invalid) for periodOrder
        $rep->repeat_freq = 'bad';
        $rep->periodOrder();

    }

    /**
     * @expectedException \Firefly\Exception\FireflyException
     */
    public function testLimitrepetitionContinued()
    {
        $limit = f::create('Limit');
        $rep = f::create('LimitRepetition');
        $budget = f::create('Budget');
        $journal = f::create('TransactionJournal');
        $one = f::create('Transaction');
        $two = f::create('Transaction');
        $one->amount = 300;
        $two->amount = -300;

        $rep->limit()->associate($limit);
        $limit->budget()->associate($budget);
        $journal->transactions()->save($one);
        $journal->transactions()->save($two);
        $journal->budgets()->save($budget);

        // repeat frequency (not present) for periodShow
        $testDate = new Carbon;
        $testDate->startOfMonth();
        $rep->repeat_freq = null;
        $this->assertEquals($testDate->format('F Y'), $rep->periodShow());

        // repeat frequency (present) for periodOrder
        $list = ['yearly', 'half-year', 'quarterly', 'monthly', 'weekly', 'daily'];
        foreach ($list as $index => $entry) {
            $rep->repeat_freq = $entry;
            $this->assertGreaterThan(0, strlen($rep->periodShow()));
        }

        // repeat freq (invalid) for periodOrder
        $rep->repeat_freq = 'bad';
        $rep->periodShow();
    }

    public function testPiggybank()
    {
        $piggy = f::create('Piggybank');
        $account = f::create('Account');
        $piggy->account()->associate($account);

        $this->assertEquals($account->id, $piggy->account_id);
    }

    public function testPreference()
    {
        $pref = f::create('Preference');
        $user = f::create('User');
        $pref->user()->associate($user);
        $this->assertEquals($pref->user_id, $user->id);
        $pref->data = 'Hello';
        $this->assertEquals($pref->data, 'Hello');
    }

    public function testRecurringtransaction()
    {
        $rec = f::create('RecurringTransaction');
        $user = f::create('User');
        $rec->user()->associate($user);
        $this->assertEquals($rec->user_id, $user->id);

        $list = ['yearly', 'half-year', 'quarterly', 'monthly', 'weekly', 'daily'];
        foreach ($list as $index => $entry) {
            $start = clone $rec->date;
            $rec->repeat_freq = $entry;
            $end = $rec->next();
            $this->assertTrue($end > $start);
        }
    }

    public function testTransaction()
    {
        $transaction = f::create('Transaction');
        $journal = f::create('TransactionJournal');
        $component = f::create('Component');
        $budget = f::create('Budget');
        $category = f::create('Category');
        $account = f::create('Account');

        $transaction->transactionJournal()->associate($journal);
        $this->assertEquals($transaction->transaction_journal_id, $journal->id);
        $transaction->components()->save($component);
        $this->assertEquals($transaction->components()->first()->id, $component->id);
        $transaction->budgets()->save($budget);
        $this->assertEquals($transaction->budgets()->first()->id, $budget->id);
        $transaction->categories()->save($category);
        $this->assertEquals($transaction->categories()->first()->id, $category->id);
        $transaction->account()->associate($account);
        $this->assertEquals($transaction->account_id, $account->id);
    }

    public function testTransactionCurrency()
    {
        $cur = f::create('TransactionCurrency');
        $journal = f::create('TransactionJournal');
        $cur->transactionjournals()->save($journal);
        $journal->save();
        $cur->save();
        $this->assertEquals($cur->id, $journal->transaction_currency_id);
    }

    public function testTransactionJournal()
    {
        $journal = f::create('TransactionJournal');
        $type = f::create('TransactionType');
        $user = f::create('User');
        $cur = f::create('TransactionCurrency');
        $transaction = f::create('Transaction');
        $component = f::create('Component');
        $budget = f::create('Budget');
        $category = f::create('Category');

        $journal->transactionType()->associate($type);
        $this->assertEquals($type->id, $journal->transaction_type_id);

        $journal->user()->associate($user);
        $this->assertEquals($user->id, $journal->user_id);

        $journal->transactionCurrency()->associate($cur);
        $this->assertEquals($cur->id, $journal->transaction_currency_id);

        $journal->transactions()->save($transaction);
        $this->assertEquals($journal->transactions()->first()->id, $transaction->id);

        $journal->components()->save($component);
        $this->assertEquals($journal->components()->first()->id, $component->id);

        $journal->budgets()->save($budget);
        $this->assertEquals($journal->budgets()->first()->id, $budget->id);

        $journal->categories()->save($category);
        $this->assertEquals($journal->categories()->first()->id, $category->id);
    }

    public function testTransactionType()
    {
        $journal = f::create('TransactionJournal');
        $type = f::create('TransactionType');

        $type->transactionjournals()->save($journal);
        $this->assertEquals($type->transactionJournals()->first()->id, $journal->id);

    }

    public function testUser()
    {
        $user = f::create('User');

        $account = f::create('Account');
        $bud = f::create('Budget');
        $cat = f::create('Category');
        $comp = f::create('Component');
        $pref = f::create('Preference');
        $rec = f::create('RecurringTransaction');
        $journal = f::create('TransactionJournal');

        $user->accounts()->save($account);
        $this->assertEquals($account->id, $user->accounts()->first()->id);

        $user->components()->save($comp);
        $this->assertEquals($comp->id, $user->components()->first()->id);

        $user->budgets()->save($bud);
        $this->assertEquals($bud->id, $user->budgets()->first()->id);

        $user->categories()->save($cat);
        $this->assertEquals($cat->id, $user->categories()->first()->id);

        $user->preferences()->save($pref);
        $this->assertEquals($pref->id, $user->preferences()->first()->id);

        $user->recurringtransactions()->save($rec);
        $this->assertEquals($rec->id, $user->recurringtransactions()->first()->id);

        $user->transactionjournals()->save($journal);
        $this->assertEquals($journal->id, $user->transactionjournals()->first()->id);
    }


}