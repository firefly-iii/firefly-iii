<?php

use Carbon\Carbon;

/**
 *
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 *
 * Class TestContentSeeder
 */
class TestContentSeeder extends Seeder
{
    /** @var  string */
    public $eom;
    /** @var  string */
    public $neom;
    /** @var  string */
    public $nsom;
    /** @var  string */
    public $som;
    /** @var  string */
    public $today;
    /** @var  string */
    public $yaeom;
    /** @var  string */
    public $yasom;
    /** @var Carbon */
    protected $_endOfMonth;
    /** @var Carbon */
    protected $_nextEndOfMonth;
    /** @var Carbon */
    protected $_nextStartOfMonth;
    /** @var Carbon */
    protected $_startOfMonth;
    /** @var  Carbon */
    protected $_today;
    /** @var  Carbon */
    protected $_yearAgoEndOfMonth;
    /** @var  Carbon */
    protected $_yearAgoStartOfMonth;

    /**
     *
     */
    public function __construct()
    {
        $this->_startOfMonth = Carbon::now()->startOfMonth();
        $this->som           = $this->_startOfMonth->format('Y-m-d');

        $this->_endOfMonth = Carbon::now()->endOfMonth();
        $this->eom         = $this->_endOfMonth->format('Y-m-d');

        $this->_nextStartOfMonth = Carbon::now()->addMonth()->startOfMonth();
        $this->nsom              = $this->_nextStartOfMonth->format('Y-m-d');

        $this->_nextEndOfMonth = Carbon::now()->addMonth()->endOfMonth();
        $this->neom            = $this->_nextEndOfMonth->format('Y-m-d');

        $this->_yearAgoStartOfMonth = Carbon::now()->subYear()->startOfMonth();
        $this->yasom                = $this->_yearAgoStartOfMonth->format('Y-m-d');

        $this->_yearAgoEndOfMonth = Carbon::now()->subYear()->startOfMonth();
        $this->yaeom              = $this->_yearAgoEndOfMonth->format('Y-m-d');


        $this->_today = Carbon::now();
        $this->today  = $this->_today->format('Y-m-d');
    }

    /**
     * Dates are always this month, the start of this month or earlier.
     */
    public function run()
    {
        if (App::environment() == 'testing' || App::environment() == 'homestead') {

            $user = User::whereEmail('thegrumpydictator@gmail.com')->first();

            // create initial accounts and various other stuff:
            $this->createAssetAccounts($user);
            $this->createBudgets($user);
            $this->createCategories($user);
            $this->createPiggyBanks($user);
            $this->createReminders($user);
            $this->createRecurringTransactions($user);
            $this->createBills($user);
            $this->createExpenseAccounts($user);
            $this->createRevenueAccounts($user);

            // get some objects from the database:
            $checking   = Account::whereName('Checking account')->orderBy('id', 'DESC')->first();
            $savings    = Account::whereName('Savings account')->orderBy('id', 'DESC')->first();
            $landLord   = Account::whereName('Land lord')->orderBy('id', 'DESC')->first();
            $utilities  = Account::whereName('Utilities company')->orderBy('id', 'DESC')->first();
            $television = Account::whereName('TV company')->orderBy('id', 'DESC')->first();
            $phone      = Account::whereName('Phone agency')->orderBy('id', 'DESC')->first();
            $employer   = Account::whereName('Employer')->orderBy('id', 'DESC')->first();


            $bills     = Budget::whereName('Bills')->orderBy('id', 'DESC')->first();
            $groceries = Budget::whereName('Groceries')->orderBy('id', 'DESC')->first();

            $house = Category::whereName('House')->orderBy('id', 'DESC')->first();


            $withdrawal = TransactionType::whereType('Withdrawal')->first();
            $deposit    = TransactionType::whereType('Deposit')->first();
            $transfer   = TransactionType::whereType('Transfer')->first();

            $euro = TransactionCurrency::whereCode('EUR')->first();

            $rentBill = Bill::where('name', 'Rent')->first();


            $current = clone $this->_yearAgoStartOfMonth;
            while ($current <= $this->_startOfMonth) {
                $cur       = $current->format('Y-m-d');
                $formatted = $current->format('F Y');

                // create expenses for rent, utilities, TV, phone on the 1st of the month.
                $this->createTransaction($checking, $landLord, 800, $withdrawal, 'Rent for ' . $formatted, $cur, $euro, $bills, $house, $rentBill);
                $this->createTransaction($checking, $utilities, 150, $withdrawal, 'Utilities for ' . $formatted, $cur, $euro, $bills, $house);
                $this->createTransaction($checking, $television, 50, $withdrawal, 'TV for ' . $formatted, $cur, $euro, $bills, $house);
                $this->createTransaction($checking, $phone, 50, $withdrawal, 'Phone bill for ' . $formatted, $cur, $euro, $bills, $house);

                // two transactions. One without a budget, one without a category.
                $this->createTransaction($checking, $phone, 10, $withdrawal, 'Extra charges on phone bill for ' . $formatted, $cur, $euro, null, $house);
                $this->createTransaction($checking, $television, 5, $withdrawal, 'Extra charges on TV bill for ' . $formatted, $cur, $euro, $bills, null);

                // income from job:
                $this->createTransaction($employer, $checking, rand(3500, 4000), $deposit, 'Salary for ' . $formatted, $cur, $euro);
                $this->createTransaction($checking, $savings, 2000, $transfer, 'Salary to savings account in ' . $formatted, $cur, $euro);

                $this->createGroceries($current);
                $this->createBigExpense(clone $current);

                echo 'Created test-content for ' . $current->format('F Y') . "\n";
                $current->addMonth();
            }


            // piggy bank event
            // add money to this piggy bank
            // create a piggy bank event to match:
            $piggyBank = PiggyBank::whereName('New camera')->orderBy('id', 'DESC')->first();
            $intoPiggy = $this->createTransaction($checking, $savings, 100, $transfer, 'Money for piggy', $this->yaeom, $euro, $groceries, $house);
            PiggyBankEvent::create(
                [
                    'piggy_bank_id'          => $piggyBank->id,
                    'transaction_journal_id' => $intoPiggy->id,
                    'date'                   => $this->yaeom,
                    'amount'                 => 100
                ]
            );
        }
    }

    /**
     * @param User $user
     */
    public function createAssetAccounts(User $user)
    {
        $assetType = AccountType::whereType('Asset account')->first();
        $ibType    = AccountType::whereType('Initial balance account')->first();
        $obType    = TransactionType::whereType('Opening balance')->first();
        $euro      = TransactionCurrency::whereCode('EUR')->first();


        $acc_a = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Checking account', 'active' => 1]);
        $acc_b = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Savings account', 'active' => 1]);
        $acc_c = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Delete me', 'active' => 1]);

        $acc_d = Account::create(['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Checking account initial balance', 'active' => 0]);
        $acc_e = Account::create(['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Savings account initial balance', 'active' => 0]);
        $acc_f = Account::create(['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Delete me initial balance', 'active' => 0]);


        $this->createTransaction($acc_d, $acc_a, 4000, $obType, 'Initial Balance for Checking account', $this->yasom, $euro);
        $this->createTransaction($acc_e, $acc_b, 10000, $obType, 'Initial Balance for Savings account', $this->yasom, $euro);
        $this->createTransaction($acc_f, $acc_c, 100, $obType, 'Initial Balance for Delete me', $this->yasom, $euro);
    }

    /**
     * @param Account              $from
     * @param Account              $to
     * @param                      $amount
     * @param TransactionType      $type
     * @param                      $description
     * @param                      $date
     * @param TransactionCurrency  $currency
     *
     * @param Budget               $budget
     * @param Category             $category
     * @param Bill                 $bill
     *
     * @return TransactionJournal
     */
    public function createTransaction(
        Account $from, Account $to, $amount, TransactionType $type, $description, $date, TransactionCurrency $currency, Budget $budget = null,
        Category $category = null, Bill $bill = null
    ) {
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();

        $billID = is_null($bill) ? null : $bill->id;


        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::create(
            [
                'user_id'     => $user->id, 'transaction_type_id' => $type->id, 'transaction_currency_id' => $currency->id, 'bill_id' => $billID,
                'description' => $description, 'completed' => 1, 'date' => $date
            ]
        );

        Transaction::create(['account_id' => $from->id, 'transaction_journal_id' => $journal->id, 'amount' => $amount * -1]);
        Transaction::create(['account_id' => $to->id, 'transaction_journal_id' => $journal->id, 'amount' => $amount]);

        if (!is_null($budget)) {
            $journal->budgets()->save($budget);
        }
        if (!is_null($category)) {
            $journal->categories()->save($category);
        }

        return $journal;
    }

    /**
     * @param User $user
     */
    public function createBudgets(User $user)
    {

        $groceries = Budget::create(['user_id' => $user->id, 'name' => 'Groceries']);
        $bills     = Budget::create(['user_id' => $user->id, 'name' => 'Bills']);
        $deleteMe  = Budget::create(['user_id' => $user->id, 'name' => 'Delete me']);
        Budget::create(['user_id' => $user->id, 'name' => 'Budget without repetition']);
        $groceriesLimit = BudgetLimit::create(
            ['startdate' => $this->som, 'amount' => 201, 'repeats' => 0, 'repeat_freq' => 'monthly', 'budget_id' => $groceries->id]
        );
        $billsLimit     = BudgetLimit::create(
            ['startdate' => $this->som, 'amount' => 202, 'repeats' => 0, 'repeat_freq' => 'monthly', 'budget_id' => $bills->id]
        );
        $deleteMeLimit  = BudgetLimit::create(
            ['startdate' => $this->som, 'amount' => 203, 'repeats' => 0, 'repeat_freq' => 'monthly', 'budget_id' => $deleteMe->id]
        );

        // and because we have no filters, some repetitions:
        LimitRepetition::create(['budget_limit_id' => $groceriesLimit->id, 'startdate' => $this->som, 'enddate' => $this->eom, 'amount' => 201]);
        LimitRepetition::create(['budget_limit_id' => $billsLimit->id, 'startdate' => $this->som, 'enddate' => $this->eom, 'amount' => 202]);
        LimitRepetition::create(['budget_limit_id' => $deleteMeLimit->id, 'startdate' => $this->som, 'enddate' => $this->eom, 'amount' => 203]);
    }

    /**
     * @param User $user
     */
    public function createCategories(User $user)
    {
        Category::create(['user_id' => $user->id, 'name' => 'DailyGroceries']);
        Category::create(['user_id' => $user->id, 'name' => 'Lunch']);
        Category::create(['user_id' => $user->id, 'name' => 'House']);
        Category::create(['user_id' => $user->id, 'name' => 'Delete me']);

    }

    /**
     * @param User $user
     */
    public function createPiggyBanks(User $user)
    {
        // account:
        $savings = Account::whereName('Savings account')->orderBy('id', 'DESC')->first();

        // some dates:
        $endDate  = clone $this->_startOfMonth;
        $nextYear = clone $this->_startOfMonth;

        $endDate->addMonths(4);
        $nextYear->addYear()->subDay();

        $next = $nextYear->format('Y-m-d');
        $end  = $endDate->format('Y-m-d');

        // piggy bank
        $newCamera = PiggyBank::create(
            [
                'account_id'    => $savings->id,
                'name'          => 'New camera',
                'targetamount'  => 2000,
                'startdate'     => $this->som,
                'targetdate'    => null,
                'repeats'       => 0,
                'rep_length'    => null,
                'rep_every'     => 0,
                'rep_times'     => null,
                'reminder'      => null,
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 0,
            ]
        );
        // and some events!
        PiggyBankEvent::create(['piggy_bank_id' => $newCamera->id, 'date' => $this->som, 'amount' => 100]);
        PiggyBankRepetition::create(['piggy_bank_id' => $newCamera->id, 'startdate' => $this->som, 'targetdate' => null, 'currentamount' => 100]);


        $newClothes = PiggyBank::create(
            [
                'account_id'    => $savings->id,
                'name'          => 'New clothes',
                'targetamount'  => 2000,
                'startdate'     => $this->som,
                'targetdate'    => $end,
                'repeats'       => 0,
                'rep_length'    => null,
                'rep_every'     => 0,
                'rep_times'     => null,
                'reminder'      => null,
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 0,
            ]
        );

        PiggyBankEvent::create(['piggy_bank_id' => $newClothes->id, 'date' => $this->som, 'amount' => 100]);
        PiggyBankRepetition::create(['piggy_bank_id' => $newClothes->id, 'startdate' => $this->som, 'targetdate' => $end, 'currentamount' => 100]);

        // weekly reminder piggy bank
        $weekly = PiggyBank::create(
            [
                'account_id'    => $savings->id,
                'name'          => 'Weekly reminder for clothes',
                'targetamount'  => 2000,
                'startdate'     => $this->som,
                'targetdate'    => $next,
                'repeats'       => 0,
                'rep_length'    => null,
                'rep_every'     => 0,
                'rep_times'     => null,
                'reminder'      => 'week',
                'reminder_skip' => 0,
                'remind_me'     => 1,
                'order'         => 0,
            ]
        );
        PiggyBankRepetition::create(['piggy_bank_id' => $weekly->id, 'startdate' => $this->som, 'targetdate' => $next, 'currentamount' => 0]);
    }

    /**
     * @param User $user
     */
    public function createReminders(User $user)
    {
        // for weekly piggy bank (clothes)
        $nextWeek  = clone $this->_startOfMonth;
        $piggyBank = PiggyBank::whereName('New clothes')->orderBy('id', 'DESC')->first();
        $nextWeek->addWeek();
        $week = $nextWeek->format('Y-m-d');

        Reminder::create(
            ['user_id'          => $user->id, 'startdate' => $this->som, 'enddate' => $week, 'active' => 1, 'notnow' => 0,
             'remindersable_id' => $piggyBank->id, 'remindersable_type' => 'PiggyBank']
        );

        // a fake reminder::
        Reminder::create(
            ['user_id'            => $user->id, 'startdate' => $this->som, 'enddate' => $week, 'active' => 0, 'notnow' => 0, 'remindersable_id' => 40,
             'remindersable_type' => 'Transaction']
        );
    }

    /**
     * @param User $user
     */
    public function createRecurringTransactions(User $user)
    {
        // account:
        $savings = Account::whereName('Savings account')->orderBy('id', 'DESC')->first();

        $recurring = PiggyBank::create(
            [
                'account_id'    => $savings->id,
                'name'          => 'Nieuwe spullen',
                'targetamount'  => 1000,
                'startdate'     => $this->som,
                'targetdate'    => $this->eom,
                'repeats'       => 1,
                'rep_length'    => 'month',
                'rep_every'     => 0,
                'rep_times'     => 0,
                'reminder'      => 'month',
                'reminder_skip' => 0,
                'remind_me'     => 1,
                'order'         => 0,
            ]
        );
        PiggyBankRepetition::create(['piggy_bank_id' => $recurring->id, 'startdate' => $this->som, 'targetdate' => $this->eom, 'currentamount' => 0]);
        PiggyBankRepetition::create(
            ['piggy_bank_id' => $recurring->id, 'startdate' => $this->nsom, 'targetdate' => $this->neom, 'currentamount' => 0]
        );
        Reminder::create(
            ['user_id'          => $user->id, 'startdate' => $this->som, 'enddate' => $this->neom, 'active' => 1, 'notnow' => 0,
             'remindersable_id' => $recurring->id, 'remindersable_type' => 'PiggyBank']
        );
    }

    /**
     * @param $user
     */
    public function createBills($user)
    {
        // bill
        Bill::create(
            [
                'user_id'     => $user->id, 'name' => 'Rent', 'match' => 'rent,landlord',
                'amount_min'  => 700,
                'amount_max'  => 900,
                'date'        => $this->som,
                'active'      => 1,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );

        // bill
        Bill::create(
            [
                'user_id'     => $user->id,
                'name'        => 'Gas licht',
                'match'       => 'no,match',
                'amount_min'  => 500,
                'amount_max'  => 700,
                'date'        => $this->som,
                'active'      => 1,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );

        // bill
        Bill::create(
            [
                'user_id'     => $user->id,
                'name'        => 'Something something',
                'match'       => 'mumble,mumble',
                'amount_min'  => 500,
                'amount_max'  => 700,
                'date'        => $this->som,
                'active'      => 0,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );

    }

    /**
     * @param $user
     */
    public function createExpenseAccounts($user)
    {
        //// create expenses for rent, utilities, water, TV, phone on the 1st of the month.
        $expenseType = AccountType::whereType('Expense account')->first();

        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Land lord', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Utilities company', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Water company', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'TV company', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Phone agency', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Super savers', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Groceries House', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Lunch House', 'active' => 1]);


        Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Buy More', 'active' => 1]);

    }

    /**
     * @param $user
     */
    public function createRevenueAccounts($user)
    {
        $revenueType = AccountType::whereType('Revenue account')->first();

        Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Employer', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'IRS', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Second job employer', 'active' => 1]);

    }

    /**
     * @param Carbon $date
     */
    public function createGroceries(Carbon $date)
    {
        // variables we need:
        $checking   = Account::whereName('Checking account')->orderBy('id', 'DESC')->first();
        $shopOne    = Account::whereName('Groceries House')->orderBy('id', 'DESC')->first();
        $shopTwo    = Account::whereName('Super savers')->orderBy('id', 'DESC')->first();
        $lunchHouse = Account::whereName('Lunch House')->orderBy('id', 'DESC')->first();
        $lunch      = Category::whereName('Lunch')->orderBy('id', 'DESC')->first();
        $daily      = Category::whereName('DailyGroceries')->orderBy('id', 'DESC')->first();
        $euro       = TransactionCurrency::whereCode('EUR')->first();
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        $groceries  = Budget::whereName('Groceries')->orderBy('id', 'DESC')->first();


        $shops = [$shopOne, $shopTwo];

        // create groceries and lunch (daily, between 5 and 10 euro).
        $mStart = clone $date;
        $mEnd   = clone $date;
        $mEnd->endOfMonth();
        while ($mStart <= $mEnd) {
            $mFormat = $mStart->format('Y-m-d');
            $shop    = $shops[rand(0, 1)];

            $this->createTransaction($checking, $shop, (rand(500, 1000) / 100), $withdrawal, 'Groceries', $mFormat, $euro, $groceries, $daily);
            $this->createTransaction($checking, $lunchHouse, (rand(200, 600) / 100), $withdrawal, 'Lunch', $mFormat, $euro, $groceries, $lunch);

            $mStart->addDay();
        }
    }

    public function createBigExpense($date)
    {
        $date->addDays(12);
        $dollar     = TransactionCurrency::whereCode('USD')->first();
        $checking   = Account::whereName('Checking account')->orderBy('id', 'DESC')->first();
        $savings    = Account::whereName('Savings account')->orderBy('id', 'DESC')->first();
        $buyMore    = Account::whereName('Buy More')->orderBy('id', 'DESC')->first();
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        $transfer   = TransactionType::whereType('Transfer')->first();
        $user       = User::whereEmail('thegrumpydictator@gmail.com')->first();


        // create some big expenses, move some money around.
        $amount = rand(500, 2000);
        $one    = $this->createTransaction(
            $savings, $checking, $amount, $transfer, 'Money for big expense in ' . $date->format('F Y'), $date->format('Y-m-d'), $dollar
        );
        $two    = $this->createTransaction(
            $checking, $buyMore, $amount, $withdrawal, 'Big expense in ' . $date->format('F Y'), $date->format('Y-m-d'), $dollar
        );
        $group  = TransactionGroup::create(
            [
                'user_id'  => $user->id,
                'relation' => 'balance'
            ]
        );
        $group->transactionjournals()->save($one);
        $group->transactionjournals()->save($two);
    }
} 