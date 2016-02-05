<?php

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Category;
use FireflyIII\Models\Role;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\Migration\TestData;
use FireflyIII\User;
use Illuminate\Database\Seeder;

/**
 * Class OldTestDataSeeder
 */
class OldTestDataSeeder extends Seeder
{
    /** @var  Carbon */
    public $start;

    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {
        $this->start = Carbon::create()->subYear()->startOfYear();

    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create budget limits for these budgets
//        TestData::createBudgetLimit($user, new Carbon, 'Groceries', 400);
//        TestData::createBudgetLimit($user, new Carbon, 'Bills', 1000);
//        TestData::createBudgetLimit($user, new Carbon, 'Car', 100);

        // create some categories for user #1
        $this->createCategories($user);

        // create some piggy banks for user #1
        TestData::createPiggybanks($user);

        // create some expense accounts for user #1
        $this->createExpenseAccounts($user);

        // create some revenue accounts for user #1
        $this->createRevenueAccounts($user);

        // create journal + attachment:
        TestData::createAttachments($user, $this->start);

        // create opening balance for savings account:
        $this->openingBalanceSavings($user);

        // need at least one rule group and one rule:
        TestData::createRules($user);

        // create a tag:
        TestData::createTags($user);
    }

}
