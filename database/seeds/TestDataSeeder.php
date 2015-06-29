<?php
use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Database\Seeder;

/**
 * @SuppressWarnings("CamelCase") // I'm fine with this.
 * @SuppressWarnings("TooManyMethods") // I'm fine with this
 * @SuppressWarnings("CouplingBetweenObjects") // I'm fine with this
 * @SuppressWarnings("MethodLength") // I'm fine with this
 *
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
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
     * A whole bunch of times and dates.
     */
    public function __construct()
    {
        $this->_startOfMonth        = Carbon::now()->startOfMonth();
        $this->som                  = $this->_startOfMonth->format('Y-m-d');
        $this->_endOfMonth          = Carbon::now()->endOfMonth();
        $this->eom                  = $this->_endOfMonth->format('Y-m-d');
        $this->_nextStartOfMonth    = Carbon::now()->addMonth()->startOfMonth();
        $this->nsom                 = $this->_nextStartOfMonth->format('Y-m-d');
        $this->_nextEndOfMonth      = Carbon::now()->addMonth()->endOfMonth();
        $this->neom                 = $this->_nextEndOfMonth->format('Y-m-d');
        $this->_yearAgoStartOfMonth = Carbon::now()->subYear()->startOfMonth();
        $this->yasom                = $this->_yearAgoStartOfMonth->format('Y-m-d');
        $this->_yearAgoEndOfMonth   = Carbon::now()->subYear()->startOfMonth();
        $this->yaeom                = $this->_yearAgoEndOfMonth->format('Y-m-d');
        $this->_today               = Carbon::now();
        $this->today                = $this->_today->format('Y-m-d');
    }

    /**
     * Dates are always this month, the start of this month or earlier.
     */
    public function run()
    {
        $this->createUsers();
        $this->createAssetAccounts();
        $this->createBudgets();
        $this->createCategories();
        $this->createPiggyBanks();
        $this->createBills();
        $this->createExpenseAccounts();
        $this->createRevenueAccounts();
        $this->createTags();

        $current = clone $this->_yearAgoStartOfMonth;
        while ($current <= $this->_startOfMonth) {

            // create expenses for rent, utilities, TV, phone on the 1st of the month.
            $this->createMonthlyExpenses(clone $current);
            $this->createGroceries(clone $current);
            $this->createBigExpense(clone $current);

            echo 'Created test-content for ' . $current->format('F Y') . "\n";
            $current->addMonth();
        }
        $this->createPiggyBankEvent();


    }

    /**
     *
     */
    public function createUsers()
    {
        User::create(
            ['email' => 'reset@example.com', 'password' => bcrypt('functional'), 'reset' => 'okokokokokokokokokokokokokokokok', 'remember_token' => null]
        );
        User::create(['email' => 'functional@example.com', 'password' => bcrypt('functional'), 'reset' => null, 'remember_token' => null]);
        User::create(['email' => 'thegrumpydictator@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
    }

    /**
     *
     */
    public function createAssetAccounts()
    {
        $user      = User::whereEmail('thegrumpydictator@gmail.com')->first();
        $assetType = AccountType::whereType('Asset account')->first();
        $ibType    = AccountType::whereType('Initial balance account')->first();
        $obType    = TransactionType::whereType('Opening balance')->first();
        $euro      = TransactionCurrency::whereCode('EUR')->first();


        $acc_a = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Checking account', 'active' => 1]);
        $acc_b = Account::create(['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Savings account', 'active' => 1]);
        $acc_c = Account::create(
            ['user_id' => $user->id, 'account_type_id' => $assetType->id, 'name' => 'Delete me', 'active' => 1, 'virtual_balance' => 123.45]
        );

        // create account meta:
        AccountMeta::create(['account_id' => $acc_a->id, 'name' => 'accountRole', 'data' => 'defaultAsset']);
        AccountMeta::create(['account_id' => $acc_b->id, 'name' => 'accountRole', 'data' => 'savingAsset']);
        AccountMeta::create(['account_id' => $acc_c->id, 'name' => 'accountRole', 'data' => 'defaultAsset']);

        $acc_d = Account::create(['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Checking account initial balance', 'active' => 0]);
        $acc_e = Account::create(['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Savings account initial balance', 'active' => 0]);
        $acc_f = Account::create(['user_id' => $user->id, 'account_type_id' => $ibType->id, 'name' => 'Delete me initial balance', 'active' => 0]);

        $this->createJournal(
            ['from' => $acc_d, 'to' => $acc_a, 'amount' => 4000, 'transactionType' => $obType, 'description' => 'Initial Balance for Checking account',
             'date' => $this->yasom, 'transactionCurrency' => $euro]
        );
        $this->createJournal(
            ['from' => $acc_e, 'to' => $acc_b, 'amount' => 10000, 'transactionType' => $obType, 'description' => 'Initial Balance for Savings account',
             'date' => $this->yasom, 'transactionCurrency' => $euro]
        );
        $this->createJournal(
            ['from' => $acc_f, 'to' => $acc_c, 'amount' => 100, 'transactionType' => $obType, 'description' => 'Initial Balance for Delete me',
             'date' => $this->yasom, 'transactionCurrency' => $euro]
        );


    }

    /**
     * @param array $data
     *
     * @return TransactionJournal
     */
    public function createJournal(array $data)
    {
        $user   = User::whereEmail('thegrumpydictator@gmail.com')->first();
        $billID = isset($data['bill']) ? $data['bill']->id : null;

        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::create(
            [
                'user_id'                 => $user->id,
                'transaction_type_id'     => $data['transactionType']->id,
                'transaction_currency_id' => $data['transactionCurrency']->id,
                'bill_id'                 => $billID,
                'description'             => $data['description'],
                'completed'               => 1,
                'date'                    => $data['date']
            ]
        );

        Transaction::create(['account_id' => $data['from']->id, 'transaction_journal_id' => $journal->id, 'amount' => $data['amount'] * -1]);
        Transaction::create(['account_id' => $data['to']->id, 'transaction_journal_id' => $journal->id, 'amount' => $data['amount']]);

        if (isset($data['budget'])) {
            $journal->budgets()->save($data['budget']);
        }
        if (isset($data['category'])) {
            $journal->categories()->save($data['category']);
        }

        return $journal;
    }

    /**
     *
     */
    public function createBudgets()
    {
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();

        $groceries = Budget::create(['user_id' => $user->id, 'name' => 'Groceries']);
        $bills     = Budget::create(['user_id' => $user->id, 'name' => 'Bills']);
        $deleteMe  = Budget::create(['user_id' => $user->id, 'name' => 'Delete me']);
        Budget::create(['user_id' => $user->id, 'name' => 'Budget without repetition']);
        BudgetLimit::create(
            ['startdate' => $this->som, 'amount' => 201, 'repeats' => 0, 'repeat_freq' => 'monthly', 'budget_id' => $groceries->id]
        );
        BudgetLimit::create(
            ['startdate' => $this->som, 'amount' => 202, 'repeats' => 0, 'repeat_freq' => 'monthly', 'budget_id' => $bills->id]
        );
        BudgetLimit::create(
            ['startdate' => $this->som, 'amount' => 203, 'repeats' => 0, 'repeat_freq' => 'monthly', 'budget_id' => $deleteMe->id]
        );

        // and because we have no filters, some repetitions:
    }

    /**
     *
     */
    public function createCategories()
    {
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        Category::create(['user_id' => $user->id, 'name' => 'DailyGroceries']);
        Category::create(['user_id' => $user->id, 'name' => 'Lunch']);
        Category::create(['user_id' => $user->id, 'name' => 'House']);
        Category::create(['user_id' => $user->id, 'name' => 'Delete me']);

    }

    /**
     *
     */
    public function createPiggyBanks()
    {
        // account
        $savings = $this->findAccount('Savings account');


        // some dates
        $endDate  = clone $this->_startOfMonth;
        $nextYear = clone $this->_startOfMonth;

        $endDate->addMonths(4);
        $nextYear->addYear()->subDay();

        $end = $endDate->format('Y-m-d');

        // piggy bank
        $newCamera = PiggyBank::create(
            [
                'account_id'    => $savings->id,
                'name'          => 'New camera',
                'targetamount'  => 2000,
                'startdate'     => $this->som,
                'targetdate'    => null,
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 0,
            ]
        );
        // and some events!
        PiggyBankEvent::create(['piggy_bank_id' => $newCamera->id, 'date' => $this->som, 'amount' => 100]);


        $newClothes = PiggyBank::create(
            [
                'account_id'    => $savings->id,
                'name'          => 'New clothes',
                'targetamount'  => 2000,
                'startdate'     => $this->som,
                'targetdate'    => $end,
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 0,
            ]
        );

        PiggyBankEvent::create(['piggy_bank_id' => $newClothes->id, 'date' => $this->som, 'amount' => 100]);

        /*
         * New: create no less than eight piggy banks that
         */
        $list     = ['week', 'quarter', 'month', 'year'];
        $nextYear = clone $this->_startOfMonth;
        $nextYear->addYear();
        foreach ($list as $entry) {

            PiggyBank::create(
                [
                    'account_id'    => $savings->id,
                    'name'          => $entry . ' piggy bank with target date.',
                    'targetamount'  => 1000,
                    'startdate'     => $this->som,
                    'targetdate'    => $nextYear,
                    'reminder_skip' => 0,
                    'remind_me'     => 0,
                    'order'         => 0,
                ]
            );
            PiggyBank::create(
                [
                    'account_id'    => $savings->id,
                    'name'          => $entry . ' piggy bank without target date.',
                    'targetamount'  => 1000,
                    'startdate'     => $this->som,
                    'targetdate'    => null,
                    'reminder_skip' => 0,
                    'remind_me'     => 0,
                    'order'         => 0,
                ]
            );
        }
    }

    /**
     * @param $name
     *
     * @return Account|null
     */
    protected function findAccount($name)
    {
        // account
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        /** @var Account $account */
        foreach (Account::get() as $account) {
            if ($account->name == $name && $user->id == $account->user_id) {
                return $account;
                break;
            }
        }

        return null;
    }

    /**
     *
     */
    public function createBills()
    {
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        // bill
        Bill::create(
            ['user_id' => $user->id, 'name' => 'Rent', 'match' => 'rent,land,lord', 'amount_min' => 700, 'amount_max' => 900, 'date' => $this->som,
             'active'  => 1, 'automatch' => 1, 'repeat_freq' => 'monthly', 'skip' => 0,]
        );

        // bill
        Bill::create(
            [
                'user_id'     => $user->id,
                'name'        => 'Gas licht',
                'match'       => 'no,match',
                'amount_min'  => 500, 'amount_max' => 700,
                'date'        => $this->som,
                'active'      => 1, 'automatch' => 1,
                'repeat_freq' => 'monthly', 'skip' => 0,
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
     *
     */
    public function createExpenseAccounts()
    {
        //// create expenses for rent, utilities, water, TV, phone on the 1st of the month.
        $user        = User::whereEmail('thegrumpydictator@gmail.com')->first();
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
     *
     */
    public function createRevenueAccounts()
    {
        $user        = User::whereEmail('thegrumpydictator@gmail.com')->first();
        $revenueType = AccountType::whereType('Revenue account')->first();

        Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Employer', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'IRS', 'active' => 1]);
        Account::create(['user_id' => $user->id, 'account_type_id' => $revenueType->id, 'name' => 'Second job employer', 'active' => 1]);

    }

    /**
     *
     */
    public function createTags()
    {
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();

        Tag::create(
            ['tag' => 'TagOne', 'tagMode' => 'nothing', 'user_id' => $user->id]
        );

        Tag::create(['tag' => 'TagTwo', 'tagMode' => 'nothing', 'user_id' => $user->id]);
        Tag::create(['tag' => 'TagThree', 'tagMode' => 'nothing', 'user_id' => $user->id]);
    }

    /**
     * @param Carbon $date
     */
    public function createMonthlyExpenses(Carbon $date)
    {
        // get some objects from the database:
        $checking   = $this->findAccount('Checking account');
        $savings    = $this->findAccount('Savings account');
        $landLord   = $this->findAccount('Land lord');
        $utilities  = $this->findAccount('Utilities company');
        $television = $this->findAccount('TV company');
        $phone      = $this->findAccount('Phone agency');
        $employer   = $this->findAccount('Employer');
        $bills      = $this->findBudget('Bills');
        $tagOne     = $this->findTag('TagOne');
        $tagTwo     = $this->findTag('TagTwo');
        $house      = $this->findCategory('House');
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        $deposit    = TransactionType::whereType('Deposit')->first();
        $transfer   = TransactionType::whereType('Transfer')->first();
        $euro       = TransactionCurrency::whereCode('EUR')->first();
        $rentBill   = $this->findBill('Rent');
        $cur        = $date->format('Y-m-d');
        $formatted  = $date->format('F Y');

        $this->createJournal(
            ['from' => $checking, 'to' => $landLord, 'amount' => 800, 'transactionType' => $withdrawal, 'description' => 'Rent for ' . $formatted,
             'date' => $cur, 'transactionCurrency' => $euro, 'budget' => $bills, 'category' => $house, 'bill' => $rentBill]
        );
        $this->createJournal(
            ['from' => $checking, 'to' => $utilities, 'amount' => 150, 'transactionType' => $withdrawal, 'description' => 'Utilities for ' . $formatted,
             'date' => $cur, 'transactionCurrency' => $euro, 'budget' => $bills, 'category' => $house,]
        );
        $this->createJournal(
            ['from' => $checking, 'to' => $television, 'amount' => 50, 'transactionType' => $withdrawal, 'description' => 'TV for ' . $formatted,
             'date' => $cur, 'transactionCurrency' => $euro, 'budget' => $bills, 'category' => $house,]
        );
        $jrnl = $this->createJournal(
            ['from' => $checking, 'to' => $phone, 'amount' => 50, 'transactionType' => $withdrawal, 'description' => 'Phone bill for ' . $formatted,
             'date' => $cur, 'transactionCurrency' => $euro, 'budget' => $bills, 'category' => $house,]
        );

        $jrnl->tags()->save($tagOne);


        // two transactions. One without a budget, one without a category.
        $jrnl = $this->createJournal(
            ['from'        => $checking, 'to' => $phone, 'amount' => 10, 'transactionType' => $withdrawal,
             'description' => 'Extra charges on phone bill for ' . $formatted, 'date' => $cur, 'transactionCurrency' => $euro, 'category' => $house]
        );

        $jrnl->tags()->save($tagTwo);

        $this->createJournal(
            ['from'        => $checking, 'to' => $television, 'amount' => 5, 'transactionType' => $withdrawal,
             'description' => 'Extra charges on TV bill for ' . $formatted, 'date' => $cur, 'transactionCurrency' => $euro, 'budget' => $bills]
        );

        // income from job:
        $this->createJournal(
            ['from' => $employer, 'to' => $checking, 'amount' => rand(3500, 4000), 'transactionType' => $deposit, 'description' => 'Salary for ' . $formatted,
             'date' => $cur, 'transactionCurrency' => $euro]
        );
        $this->createJournal(
            ['from'        => $checking, 'to' => $savings, 'amount' => 2000, 'transactionType' => $transfer,
             'description' => 'Salary to savings account in ' . $formatted, 'date' => $cur, 'transactionCurrency' => $euro]
        );

    }

    /**
     * @param $name
     *
     * @return Budget|null
     */
    protected function findBudget($name)
    {
        // account
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        /** @var Budget $budget */
        foreach (Budget::get() as $budget) {
            if ($budget->name == $name && $user->id == $budget->user_id) {
                return $budget;
                break;
            }
        }

        return null;
    }

    /**
     * @param $tagName
     *
     * @return Tag|null
     * @internal param $tag
     */
    protected function findTag($tagName)
    {
        // account
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        /** @var Tag $tag */
        foreach (Tag::get() as $tag) {
            if ($tag->tag == $tagName && $user->id == $tag->user_id) {
                return $tag;
                break;
            }
        }

        return null;
    }


    /**
     * @param $name
     *
     * @return Category|null
     */
    protected function findCategory($name)
    {
        // account
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        /** @var Category $category */
        foreach (Category::get() as $category) {
            if ($category->name == $name && $user->id == $category->user_id) {
                return $category;
                break;
            }
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return Bill|null
     */
    protected function findBill($name)
    {
        // account
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        /** @var Bill $bill */
        foreach (Bill::get() as $bill) {
            if ($bill->name == $name && $user->id == $bill->user_id) {
                return $bill;
                break;
            }
        }

        return null;
    }

    /**
     * @param Carbon $date
     */
    public function createGroceries(Carbon $date)
    {
        // variables we need:
        $checking   = $this->findAccount('Checking account');
        $shopOne    = $this->findAccount('Groceries House');
        $shopTwo    = $this->findAccount('Super savers');
        $lunchHouse = $this->findAccount('Lunch House');
        $lunch      = $this->findCategory('Lunch');
        $daily      = $this->findCategory('DailyGroceries');
        $euro       = TransactionCurrency::whereCode('EUR')->first();
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        $groceries  = $this->findBudget('Groceries');


        $shops = [$shopOne, $shopTwo];

        // create groceries and lunch (daily, between 5 and 10 euro).
        $mStart = clone $date;
        $mEnd   = clone $date;
        $mEnd->endOfMonth();
        while ($mStart <= $mEnd) {
            $mFormat = $mStart->format('Y-m-d');
            $shop    = $shops[rand(0, 1)];

            $this->createJournal(
                ['from' => $checking, 'to' => $shop, 'amount' => (rand(500, 1000) / 100), 'transactionType' => $withdrawal, 'description' => 'Groceries',
                 'date' => $mFormat, 'transactionCurrency' => $euro, 'category' => $daily, 'budget' => $groceries]
            );
            $this->createJournal(
                ['from' => $checking, 'to' => $lunchHouse, 'amount' => (rand(200, 600) / 100), 'transactionType' => $withdrawal, 'description' => 'Lunch',
                 'date' => $mFormat, 'transactionCurrency' => $euro, 'category' => $lunch, 'budget' => $groceries]
            );

            $mStart->addDay();
        }
    }

    /**
     * @param $date
     */
    public function createBigExpense($date)
    {
        $date->addDays(12);
        $dollar     = TransactionCurrency::whereCode('USD')->first();
        $checking   = $this->findAccount('Checking account');
        $savings    = $this->findAccount('Savings account');
        $buyMore    = $this->findAccount('Buy More');
        $withdrawal = TransactionType::whereType('Withdrawal')->first();
        $user       = User::whereEmail('thegrumpydictator@gmail.com')->first();


        // create some big expenses, move some money around.
        $amount = rand(500, 2000);

        $one   = $this->createJournal(
            ['from'        => $savings, 'to' => $checking, 'amount' => $amount, 'transactionType' => $withdrawal,
             'description' => 'Money for big expense in ' . $date->format('F Y'), 'date' => $date->format('Y-m-d'), 'transactionCurrency' => $dollar]
        );
        $two   = $this->createJournal(
            ['from'        => $checking, 'to' => $buyMore, 'amount' => $amount, 'transactionType' => $withdrawal,
             'description' => 'Big expense in ' . $date->format('F Y'), 'date' => $date->format('Y-m-d'), 'transactionCurrency' => $dollar]
        );
        $group = TransactionGroup::create(
            [
                'user_id'  => $user->id,
                'relation' => 'balance'
            ]
        );
        $group->transactionjournals()->save($one);
        $group->transactionjournals()->save($two);
        $group->save();
    }

    /**
     *
     */
    protected function createPiggyBankEvent()
    {
        // piggy bank event
        // add money to this piggy bank
        // create a piggy bank event to match:
        $checking  = $this->findAccount('Checking account');
        $savings   = $this->findAccount('Savings account');
        $transfer  = TransactionType::whereType('Transfer')->first();
        $euro      = TransactionCurrency::whereCode('EUR')->first();
        $groceries = $this->findBudget('Groceries');
        $house     = $this->findCategory('House');
        $piggyBank = $this->findPiggyBank('New camera');
        $intoPiggy = $this->createJournal(
            ['from' => $checking, 'to' => $savings, 'amount' => 100, 'transactionType' => $transfer, 'description' => 'Money for piggy',
             'date' => $this->yaeom, 'transactionCurrency' => $euro, 'category' => $house, 'budget' => $groceries]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id'          => $piggyBank->id,
                'transaction_journal_id' => $intoPiggy->id,
                'date'                   => $this->yaeom,
                'amount'                 => 100
            ]
        );
    }

    /**
     * @param $name
     *
     * @return PiggyBank|null
     */
    protected function findPiggyBank($name)
    {
        // account
        $user = User::whereEmail('thegrumpydictator@gmail.com')->first();
        /** @var Budget $budget */
        foreach (PiggyBank::get() as $piggyBank) {
            $account = $piggyBank->account()->first();
            if ($piggyBank->name == $name && $user->id == $account->user_id) {
                return $piggyBank;
                break;
            }
        }

        return null;
    }


}
