<?php

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\User;
use Illuminate\Database\Seeder;

/**
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user      = User::create(['email' => 'thegrumpydictator@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);
        $emptyUser = User::create(['email' => 'thegrumpydictator+empty@gmail.com', 'password' => bcrypt('james'), 'reset' => null, 'remember_token' => null]);

        // create asset accounts for user #1.
        $this->createAssetAccounts($user);

        // create a bills for user #1
        $this->createBills($user);

        // create some budgets for user #1
        $this->createBudgets($user);

        // create some categories for user #1
        $this->createCategories($user);
    }

    /**
     * @param User $user
     */
    private function createAssetAccounts(User $user)
    {
        $assets    = ['TestData Checking Account', 'TestData Savings', 'TestData Shared', 'TestData Creditcard', 'Emergencies', 'STE'];
        $ibans     = ['NL47JDYU6179706202', 'NL51WGBP5832453599', 'NL81RCQZ7160379858', 'NL19NRAP2367994221', 'NL40UKBK3619908726', 'NL38SRMN4325934708'];
        $assetMeta = [
            ['accountRole' => 'defaultAsset'],
            ['accountRole' => 'savingAsset',],
            ['accountRole' => 'sharedAsset',],
            ['accountRole' => 'ccAsset', 'ccMonthlyPaymentDate' => '2015-05-27', 'ccType' => 'monthlyFull',],
            ['accountRole' => 'savingAsset',],
            ['accountRole' => 'savingAsset',],
        ];

        foreach ($assets as $index => $name) {
            // create account:
            $account = Account::create(
                [
                    'user_id'         => $user->id,
                    'account_type_id' => 3,
                    'name'            => $name,
                    'active'          => 1,
                    'encrypted'       => 1,
                    'iban'            => $ibans[$index],
                ]
            );
            foreach ($assetMeta[$index] as $name => $value) {
                AccountMeta::create(['account_id' => $account->id, 'name' => $name, 'data' => $value,]);
            }
        }

    }

    /**
     * @param User $user
     */
    private function createBills(User $user)
    {
        Bill::create(
            [
                'name'        => 'Rent',
                'match'       => 'rent,land,lord',
                'amount_min'  => 795,
                'amount_max'  => 805,
                'user_id'     => $user->id,
                'date'        => '2015-01-01',
                'active'      => 1,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );
        Bill::create(
            [
                'name'        => 'Health insurance',
                'match'       => 'zilveren,kruis,health',
                'amount_min'  => 120,
                'amount_max'  => 140,
                'user_id'     => $user->id,
                'date'        => '2015-01-01',
                'active'      => 1,
                'automatch'   => 1,
                'repeat_freq' => 'monthly',
                'skip'        => 0,
            ]
        );
    }

    /**
     * @param $user
     */
    private function createBudgets($user)
    {
        $set = [
            Budget::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]),
            Budget::firstOrCreateEncrypted(['name' => 'Bills', 'user_id' => $user->id]),
        ];
        $current = new Carbon;
        /** @var Budget $budget */
        foreach ($set as $budget) {

            // some budget limits:
            $start  = clone $current;
            $end    = clone $current;
            $start->startOfMonth();
            $end->endOfMonth();

            BudgetLimit::create(
                [
                    'budget_id'   => $budget->id,
                    'startdate'   => $start->format('Y-m-d'),
                    'amount'      => 500,
                    'repeats'     => 0,
                    'repeat_freq' => 'monthly',
                ]
            );
        }
    }

    /**
     * @param User $user
     */
    private function createCategories(User $user)
    {
        Category::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Category::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);
    }
}
