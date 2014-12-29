<?php

use Carbon\Carbon;

/**
 * Class TestContentSeeder
 */
class TestContentSeeder extends Seeder
{

    public function run()
    {
        if (App::environment() == 'testing' || App::environment() == 'homestead') {

            $assetType   = AccountType::whereType('Asset account')->first();
            $expenseType = AccountType::whereType('Expense account')->first();
            $revenueType = AccountType::whereType('Revenue account')->first();
            $ibType      = AccountType::whereType('Initial balance account')->first();

            $obType     = TransactionType::whereType('Opening balance')->first();
            $withdrawal = TransactionType::whereType('Withdrawal')->first();
            $transfer   = TransactionType::whereType('Transfer')->first();
            $deposit    = TransactionType::whereType('Deposit')->first();

            $user = User::whereEmail('thegrumpydictator@gmail.com')->first();

            if ($user) {
                // create two asset accounts.
                $checking = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Checking account', 'active' => 1]);
                $savings  = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Savings account', 'active' => 1]);
                Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Delete me', 'active' => 1]);

                // create two budgets:
                $groceriesBudget = Budget::create(['user_id' => $user->id, 'name' => 'Groceries']);
                $billsBudget     = Budget::create(['user_id' => $user->id, 'name' => 'Bills']);
                $deleteBudget    = Budget::create(['user_id' => $user->id, 'name' => 'Delete me']);

                // some limits:
                $startDate   = Carbon::now()->startOfMonth();
                $endDate     = Carbon::now()->endOfMonth();
                $secondStart = Carbon::now()->subMonth()->startOfMonth();
                $secondEnd   = Carbon::now()->subMonth()->endOfMonth();
                $limitOne    = BudgetLimit::create(
                    ['startdate' => $startDate->format('Y-m-d'), 'amount' => 201, 'repeats' => 0, 'repeat_freq' => 'monthly',
                     'budget_id' => $groceriesBudget->id]
                );
                $limitTwo    = BudgetLimit::create(
                    ['startdate' => $secondStart->format('Y-m-d'), 'amount' => 202, 'repeats' => 0, 'repeat_freq' => 'monthly',
                     'budget_id' => $billsBudget->id]
                );
                $limitThree  = BudgetLimit::create(
                    ['startdate' => '2014-01-01', 'amount' => 203, 'repeats' => 0, 'repeat_freq' => 'monthly',
                     'budget_id' => $deleteBudget->id]
                );

                // and because we have no filters, some repetitions:
                $repOne   = LimitRepetition::create(
                    ['budget_limit_id' => $limitOne->id, 'startdate' => $startDate->format('Y-m-d'), 'enddate' => $endDate->format('Y-m-d'), 'amount' => 201]
                );
                $repTwo   = LimitRepetition::create(
                    ['budget_limit_id' => $limitTwo->id, 'startdate' => $secondStart->format('Y-m-d'), 'enddate' => $secondEnd->format('Y-m-d'),
                     'amount'          => 202]
                );
                $repThree = LimitRepetition::create(
                    ['budget_limit_id' => $limitThree->id, 'startdate' => '2014-01-01', 'enddate' => '2014-01-31', 'amount' => 203]
                );

                // create two categories:
                $dailyGroceries = Category::create(['user_id' => $user->id, 'name' => 'DailyGroceries']);
                $lunch          = Category::create(['user_id' => $user->id, 'name' => 'Lunch']);
                $house          = Category::create(['user_id' => $user->id, 'name' => 'House']);
                $deleteMe       = Category::create(['user_id' => $user->id, 'name' => 'Delete me']);

                Component::create(['user_id' => $user->id, 'name' => 'Some Component 1', 'class' => 'Budget']);
                Component::create(['user_id' => $user->id, 'name' => 'Some Component 2', 'class' => 'Budget']);
                Component::create(['user_id' => $user->id, 'name' => 'Some Component 3', 'class' => 'Budget']);
                Component::create(['user_id' => $user->id, 'name' => 'Some Component 4', 'class' => 'Category']);
                Component::create(['user_id' => $user->id, 'name' => 'Some Component 5', 'class' => 'Category']);
                Component::create(['user_id' => $user->id, 'name' => 'Some Component 6', 'class' => 'Category']);
                Component::create(['user_id' => $user->id, 'name' => 'Some Component 7', 'class' => 'Category']);

                // piggy bank
                $piggy = PiggyBank::create(
                    [
                        'account_id'    => $savings->id,
                        'name'          => 'New camera',
                        'targetamount'  => 2000,
                        'startdate'     => Carbon::now()->format('Y-m-d'),
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


                PiggyBankEvent::create(['piggy_bank_id' => 1, 'date' => $startDate->format('Y-m-d'), 'amount' => 100]);
                PiggyBankRepetition::create(
                    [
                        'piggy_bank_id' => $piggy->id,
                        'startdate'     => Carbon::now()->format('Y-m-d'),
                        'targetdate'    => null,
                        'currentamount' => 0
                    ]
                );

                // piggy bank
                $piggyTargeted = PiggyBank::create(
                    [
                        'account_id'    => $savings->id,
                        'name'          => 'New clothes',
                        'targetamount'  => 2000,
                        'startdate'     => Carbon::now()->format('Y-m-d'),
                        'targetdate'    => Carbon::now()->addMonths(4)->format('Y-m-d'),
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

                PiggyBankEvent::create(['piggy_bank_id' => $piggyTargeted->id, 'date' => $startDate->format('Y-m-d'), 'amount' => 100]);
                PiggyBankRepetition::create(
                    [
                        'piggy_bank_id' => $piggyTargeted->id,
                        'startdate'     => Carbon::now()->format('Y-m-d'),
                        'targetdate'    => Carbon::now()->addMonths(4)->format('Y-m-d'),
                        'currentamount' => 0
                    ]
                );

                // weekly reminder piggy bank
                $weekly = PiggyBank::create(
                    [
                        'account_id'    => $savings->id,
                        'name'          => 'Weekly reminder for clothes',
                        'targetamount'  => 2000,
                        'startdate'     => Carbon::now()->format('Y-m-d'),
                        'targetdate'    => Carbon::now()->addYear()->subDay()->format('Y-m-d'),
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
                PiggyBankRepetition::create(
                    [
                        'piggy_bank_id' => $weekly->id,
                        'startdate'     => Carbon::now()->format('Y-m-d'),
                        'targetdate'    => Carbon::now()->addYear()->subDay()->format('Y-m-d'),
                        'currentamount' => 0
                    ]
                );

                // a fake reminder for this piggy bank:
                Reminder::create(
                    [
                        'user_id'            => $user->id,
                        'startdate'          => $startDate->format('Y-m-d'),
                        'enddate'            => Carbon::now()->addWeek()->format('Y-m-d'),
                        'active'             => 1,
                        'notnow'             => 0,
                        'remindersable_id'   => $weekly->id,
                        'remindersable_type' => 'PiggyBank'
                    ]
                );

                // a fake reminder for this piggy bank:
                Reminder::create(
                    [
                        'user_id'            => $user->id,
                        'startdate'          => $startDate->format('Y-m-d'),
                        'enddate'            => Carbon::now()->addWeek()->format('Y-m-d'),
                        'active'             => 1,
                        'notnow'             => 0,
                        'remindersable_id'   => 40,
                        'remindersable_type' => 'Transaction'
                    ]
                );

                // bill
                $firstBill = \Bill::create(
                    [
                        'user_id'     => $user->id,
                        'name'        => 'Huur',
                        'match'       => 'huur,portaal',
                        'amount_min'  => 500,
                        'amount_max'  => 700,
                        'date'        => '2014-01-12',
                        'active'      => 1,
                        'automatch'   => 1,
                        'repeat_freq' => 'monthly',
                        'skip'        => 0,
                    ]
                );

                // bill
                $secondBill = \Bill::create(
                    [
                        'user_id'     => $user->id,
                        'name'        => 'Gas licht',
                        'match'       => 'no,match',
                        'amount_min'  => 500,
                        'amount_max'  => 700,
                        'date'        => '2014-01-12',
                        'active'      => 0,
                        'automatch'   => 1,
                        'repeat_freq' => 'monthly',
                        'skip'        => 0,
                    ]
                );


                // create some expense accounts.
                $albert      = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Albert Heijn', 'active' => 1]);
                $plus        = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'PLUS', 'active' => 1]);
                $vitens      = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Vitens', 'active' => 1]);
                $greenchoice = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Greenchoice', 'active' => 1]);
                $portaal     = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Portaal', 'active' => 1]);
                $store       = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Buy More', 'active' => 1]);

                // create three revenue accounts.
                $employer = Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Employer', 'active' => 1]);
                $taxes    = Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'IRS', 'active' => 1]);
                Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Job', 'active' => 1]);

                // put money in the two accounts (initial balance)
                $ibChecking = Account::create(
                    ['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Checking account initial balance', 'active' => 0]
                );
                $ibSavings  = Account::create(
                    ['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Savings account initial balance', 'active' => 0]
                );

                $this->createTransaction($ibChecking, $checking, 4000, $obType, 'Initial Balance for Checking account', '2014-01-01');
                $this->createTransaction($ibSavings, $savings, 10000, $obType, 'Initial Balance for Savings account', '2014-01-01');


                // create some expenses and incomes and what-not (for every month):
                $start = new Carbon('2014-01-01');
                $end   = Carbon::now()->endOfMonth()->addDay();
                while ($start <= $end) {
                    $this->createTransaction(
                        $checking, $portaal, 500, $withdrawal, 'Huur Portaal for ' . $start->format('F Y'), $start->format('Y-m-') . '01', $billsBudget, $house,
                        $firstBill
                    );
                    $this->createTransaction(
                        $checking, $vitens, 12, $withdrawal, 'Water for ' . $start->format('F Y'), $start->format('Y-m-') . '02', $billsBudget, $house
                    );
                    $this->createTransaction(
                        $checking, $greenchoice, 110, $withdrawal, 'Power for ' . $start->format('F Y'), $start->format('Y-m-') . '02', $billsBudget, $house
                    );

                    // spend on groceries
                    $groceriesStart = clone $start;
                    for ($i = 0; $i < 13; $i++) {
                        $amt         = rand(100, 300) / 10;
                        $lunchAmount = rand(30, 60) / 10;
                        $this->createTransaction(
                            $checking, $plus, $lunchAmount, $withdrawal, 'Lunch', $groceriesStart->format('Y-m-d'), $groceriesBudget, $lunch
                        );
                        $groceriesStart->addDay();
                        if (intval($groceriesStart->format('d')) % 2 == 0) {
                            $this->createTransaction(
                                $checking, $albert, $amt, $withdrawal, 'Groceries', $groceriesStart->format('Y-m-d'), $groceriesBudget, $dailyGroceries
                            );
                        }
                        $groceriesStart->addDay();
                    }

                    // get income:
                    $this->createTransaction($employer, $checking, rand(1400, 1600), $deposit, 'Salary', $start->format('Y-m-') . '23');

                    // pay taxes:
                    $this->createTransaction($checking, $taxes, rand(50, 70), $withdrawal, 'Taxes in ' . $start->format('F Y'), $start->format('Y-m-') . '27');

                    // some other stuff.


                    $start->addMonth();

                }

                // create some big expenses, move some money around.
                $this->createTransaction($savings, $checking, 1259, $transfer, 'Money for new PC', $end->format('Y-m') . '-11');
                $this->createTransaction($checking, $store, 1259, $withdrawal, 'New PC', $end->format('Y-m') . '-12');

                // create two budgets

                // create two categories

                // create
            }

        }
    }

    /**
     * @param Account              $from
     * @param Account              $to
     * @param                      $amount
     * @param TransactionType      $type
     * @param                      $description
     * @param                      $date
     *
     * @param Budget               $budget
     * @param Category             $category
     * @param Bill                 $bill
     *
     * @return TransactionJournal
     */
    public function createTransaction(
        Account $from, Account $to, $amount, TransactionType $type, $description, $date, Budget $budget = null, Category $category = null, Bill $bill = null
    ) {
        $user   = User::whereEmail('thegrumpydictator@gmail.com')->first();
        $euro   = TransactionCurrency::whereCode('EUR')->first();
        $billID = is_null($bill) ? null : $bill->id;

        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => $type->id,
                'transaction_currency_id' => $euro->id,
                'bill_id'                 => $billID,
                'description'             => $description,
                'completed'               => 1,
                'date'                    => $date
            ]
        );

        Transaction::create(
            [
                'account_id'             => $from->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount * -1
            ]
        );
        Transaction::create(
            [
                'account_id'             => $to->id,
                'transaction_journal_id' => $journal->id,
                'amount'                 => $amount
            ]
        );
        if (!is_null($budget)) {
            $journal->budgets()->save($budget);
        }
        if (!is_null($category)) {
            $journal->categories()->save($category);
        }

        return $journal;
    }
} 