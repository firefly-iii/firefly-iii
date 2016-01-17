<?php

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
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
}
