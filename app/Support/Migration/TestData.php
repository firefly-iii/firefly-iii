<?php
namespace FireflyIII\Support\Migration;

/**
 * TestData.php
 * Copyright (C) 2016 Sander Dorigo
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\Tag;
use FireflyIII\User;
use Log;

/**
 * Class TestData
 *
 * @package FireflyIII\Support\Migration
 */
class TestData
{

    /**
     * @param User $user
     */
    public static function createAssetAccounts(User $user)
    {
        $assets = ['TestData Checking Account', 'TestData Savings', 'TestData Shared', 'TestData Creditcard', 'Emergencies', 'STE'];
        // first two ibans match test-upload.csv
        $ibans     = ['NL11XOLA6707795988', 'NL96DZCO4665940223', 'NL81RCQZ7160379858', 'NL19NRAP2367994221', 'NL40UKBK3619908726', 'NL38SRMN4325934708'];
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
    public static function createBills(User $user)
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
     * @param User   $user
     * @param Carbon $current
     * @param        $name
     * @param        $amount
     */
    public static function createBudgetLimit(User $user, Carbon $current, $name, $amount)
    {
        $start  = clone $current;
        $end    = clone $current;
        $budget = self::findBudget($user, $name);
        $start->startOfMonth();
        $end->endOfMonth();

        BudgetLimit::create(
            [
                'budget_id'   => $budget->id,
                'startdate'   => $start->format('Y-m-d'),
                'amount'      => $amount,
                'repeats'     => 0,
                'repeat_freq' => 'monthly',
            ]
        );
    }

    /**
     * @param User $user
     */
    public static function createBudgets(User $user)
    {
        Budget::firstOrCreateEncrypted(['name' => 'Groceries', 'user_id' => $user->id]);
        Budget::firstOrCreateEncrypted(['name' => 'Bills', 'user_id' => $user->id]);
        Budget::firstOrCreateEncrypted(['name' => 'Car', 'user_id' => $user->id]);

        // some empty budgets.
        foreach (['A', 'B', 'C', 'D', "E"] as $letter) {
            Budget::firstOrCreateEncrypted(['name' => 'Empty budget ' . $letter, 'user_id' => $user->id]);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param User $user
     */
    public static function createPiggybanks(User $user)
    {
        $account = self::findAccount($user, 'TestData Savings');

        $camera                    = PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New camera',
                'targetamount'  => 1000,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 1,
            ]
        );
        $repetition                = $camera->piggyBankRepetitions()->first();
        $repetition->currentamount = 735;
        $repetition->save();

        // events:
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $camera->id,
                'date'          => '2015-05-01',
                'amount'        => '245',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $camera->id,
                'date'          => '2015-06-01',
                'amount'        => '245',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $camera->id,
                'date'          => '2015-07-01',
                'amount'        => '245',
            ]
        );


        $phone                     = PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New phone',
                'targetamount'  => 600,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 2,
            ]
        );
        $repetition                = $phone->piggyBankRepetitions()->first();
        $repetition->currentamount = 333;
        $repetition->save();

        // events:
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $phone->id,
                'date'          => '2015-05-01',
                'amount'        => '111',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $phone->id,
                'date'          => '2015-06-01',
                'amount'        => '111',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $phone->id,
                'date'          => '2015-07-01',
                'amount'        => '111',
            ]
        );

        $couch                     = PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New couch',
                'targetamount'  => 500,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 3,
            ]
        );
        $repetition                = $couch->piggyBankRepetitions()->first();
        $repetition->currentamount = 120;
        $repetition->save();

        // events:
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $couch->id,
                'date'          => '2015-05-01',
                'amount'        => '40',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $couch->id,
                'date'          => '2015-06-01',
                'amount'        => '40',
            ]
        );
        PiggyBankEvent::create(
            [
                'piggy_bank_id' => $couch->id,
                'date'          => '2015-07-01',
                'amount'        => '40',
            ]
        );

        // empty one.
        PiggyBank::create(
            [
                'account_id'    => $account->id,
                'name'          => 'New head set',
                'targetamount'  => 500,
                'startdate'     => '2015-04-01',
                'reminder_skip' => 0,
                'remind_me'     => 0,
                'order'         => 4,
            ]
        );

    }

    /**
     * @param User $user
     */
    public static function createRules(User $user)
    {
        $group = RuleGroup::create(
            [
                'user_id'     => $user->id,
                'order'       => 1,
                'title'       => trans('firefly.default_rule_group_name'),
                'description' => trans('firefly.default_rule_group_description'),
                'active'      => 1,
            ]
        );
        $rule  = Rule::create(
            [
                'user_id'         => $user->id,
                'rule_group_id'   => $group->id,
                'order'           => 1,
                'active'          => 1,
                'stop_processing' => 0,
                'title'           => trans('firefly.default_rule_name'),
                'description'     => trans('firefly.default_rule_description'),
            ]
        );

        // three triggers:
        RuleTrigger::create(
            [
                'rule_id'         => $rule->id,
                'order'           => 1,
                'active'          => 1,
                'stop_processing' => 0,
                'trigger_type'    => 'user_action',
                'trigger_value'   => 'store-journal',
            ]
        );
        RuleTrigger::create(
            [
                'rule_id'         => $rule->id,
                'order'           => 2,
                'active'          => 1,
                'stop_processing' => 0,
                'trigger_type'    => 'description_is',
                'trigger_value'   => trans('firefly.default_rule_trigger_description'),
            ]
        );
        RuleTrigger::create(
            [
                'rule_id'         => $rule->id,
                'order'           => 3,
                'active'          => 1,
                'stop_processing' => 0,
                'trigger_type'    => 'from_account_is',
                'trigger_value'   => trans('firefly.default_rule_trigger_from_account'),
            ]
        );

        // two actions:
        RuleAction::create(
            [
                'rule_id'      => $rule->id,
                'order'        => 1,
                'active'       => 1,
                'action_type'  => 'prepend_description',
                'action_value' => trans('firefly.default_rule_action_prepend'),
            ]
        );
        RuleAction::create(
            [
                'rule_id'      => $rule->id,
                'order'        => 1,
                'active'       => 1,
                'action_type'  => 'set_category',
                'action_value' => trans('firefly.default_rule_action_set_category'),
            ]
        );
    }

    /**
     * @param User        $user
     * @param Carbon|null $date
     */
    public static function createTags(User $user, Carbon $date = null)
    {
        $title = 'SomeTag nr. ' . rand(1, 1234);
        if (!is_null($date)) {
            $title = 'SomeTag' . $date->month . '.' . $date->year . '.nothing';
        }

        Tag::create(
            [
                'user_id' => $user->id,
                'tag'     => $title,
                'tagMode' => 'nothing',
                'date'    => is_null($date) ? null : $date->format('Y-m-d'),


            ]
        );
    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Account|null
     */
    public static function findAccount(User $user, $name)
    {
        /** @var Account $account */
        foreach ($user->accounts()->get() as $account) {
            if ($account->name == $name) {
                Log::debug('Trying to find "' . $name . '" in "' . $account->name . '", and found it!');

                return $account;
            }
            Log::debug('Trying to find "' . $name . '" in "' . $account->name . '".');
        }

        return null;
    }

    /**
     * @param User $user
     * @param      $name
     *
     * @return Budget|null
     */
    public static function findBudget(User $user, $name)
    {
        /** @var Budget $budget */
        foreach (Budget::get() as $budget) {
            if ($budget->name == $name && $user->id == $budget->user_id) {
                return $budget;
                break;
            }
        }

        return null;
    }


}
