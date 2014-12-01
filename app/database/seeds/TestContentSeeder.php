<?php

use Carbon\Carbon;

class TestContentSeeder extends Seeder
{

    public function run()
    {
        if (App::environment() == 'homestead') {

            $assetType   = AccountType::whereType('Asset account')->first();
            $expenseType = AccountType::whereType('Expense account')->first();
            $revenueType = AccountType::whereType('Revenue account')->first();
            $ibType      = AccountType::whereType('Initial balance account')->first();

            $euro = TransactionCurrency::whereCode('EUR')->first();

            $obType     = TransactionType::whereType('Opening balance')->first();
            $withdrawal = TransactionType::whereType('Withdrawal')->first();
            $transfer   = TransactionType::whereType('Transfer')->first();
            $deposit    = TransactionType::whereType('Deposit')->first();

            $user = User::whereEmail('thegrumpydictator@gmail.com')->first();

            if ($user) {
                // create two asset accounts.
                $checking = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Checking account', 'active' => 1]);
                $savings  = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Savings account', 'active' => 1]);

                // create two budgets:
                $groceriesBudget = Budget::create(['user_id' => $user->id, 'name' => 'Groceries']);
                $billsBudget     = Budget::create(['user_id' => $user->id, 'name' => 'Bills']);

                // create two categories:
                $dailyGroceries = Category::create(['user_id' => $user->id, 'name' => 'Daily groceries']);
                $lunch          = Category::create(['user_id' => $user->id, 'name' => 'Lunch']);
                $house          = Category::create(['user_id' => $user->id, 'name' => 'House']);

                // create some expense accounts.
                $ah          = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Albert Heijn', 'active' => 1]);
                $plus        = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'PLUS', 'active' => 1]);
                $vitens      = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Vitens', 'active' => 1]);
                $greenchoice = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Greenchoice', 'active' => 1]);
                $portaal     = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Portaal', 'active' => 1]);
                $store       = Account::create(['user_id' => $user->id, 'account_type_id' => $expenseType->id, 'name' => 'Buy More', 'active' => 1]);

                // create three revenue accounts.
                $employer = Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Employer', 'active' => 1]);
                $taxes    = Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'IRS', 'active' => 1]);
                $job      = Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Job', 'active' => 1]);

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
                $end   = Carbon::now()->startOfMonth()->subDay();
                while ($start <= $end) {
                    $this->createTransaction(
                        $checking, $portaal, 500, $withdrawal, 'Rent for ' . $start->format('F Y'), $start->format('Y-m-') . '01', $billsBudget, $house
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
                                $checking, $ah, $amt, $withdrawal, 'Groceries', $groceriesStart->format('Y-m-d'), $groceriesBudget, $dailyGroceries
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
     * @param Account         $from
     * @param Account         $to
     * @param                 $amount
     * @param TransactionType $type
     * @param                 $description
     * @param                 $date
     *
     * @return TransactionJournal
     */
    public function createTransaction(
        Account $from, Account $to, $amount, TransactionType $type, $description, $date, Budget $budget = null, Category $category = null
    ) {
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        $euro = TransactionCurrency::whereCode('EUR')->first();

        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => $type->id,
                'transaction_currency_id' => $euro->id,
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