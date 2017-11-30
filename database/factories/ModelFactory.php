<?php
/**
 * ModelFactory.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */
declare(strict_types=1);

use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(
    FireflyIII\User::class,
    function (Faker\Generator $faker) {
        static $password;

        return [
            'email'          => $faker->safeEmail,
            'password'       => $password ?: $password = bcrypt('secret'),
            'remember_token' => str_random(10),
        ];
    }
);

$factory->define(
    FireflyIII\Models\CurrencyExchangeRate::class,
    function (Faker\Generator $faker) {
        return [
            'user_id'          => 1,
            'from_currency_id' => 1,
            'to_currency_id'   => 2,
            'date'             => '2017-01-01',
            'rate'             => '1.5',
            'user_rate'        => null,
        ];
    }
);

$factory->define(
    FireflyIII\Models\TransactionCurrency::class,
    function (Faker\Generator $faker) {
        return [
            'name'   => $faker->words(1, true),
            'code'   => 'ABC',
            'symbol' => 'x',
        ];
    }
);

$factory->define(
    FireflyIII\Models\ImportJob::class,
    function (Faker\Generator $faker) {
        return [
            'id'              => $faker->numberBetween(1, 100),
            'user_id'         => 1,
            'key'             => $faker->words(1, true),
            'file_type'       => 'csv',
            'status'          => 'import_status_never_started',
            'configuration'   => null,
            'extended_status' => [
                'total_steps'  => 0,
                'steps_done'   => 0,
                'import_count' => 0,
                'importTag'    => 0,
                'errors'       => [],
            ],
        ];
    }
);

$factory->define(
    FireflyIII\Models\TransactionJournal::class,
    function (Faker\Generator $faker) {
        return [
            'id'                      => $faker->unique()->numberBetween(1000, 10000),
            'user_id'                 => 1,
            'transaction_type_id'     => 1,
            'bill_id'                 => null,
            // TODO update this transaction currency reference.
            'transaction_currency_id' => 1,
            'description'             => $faker->words(3, true),
            'date'                    => '2017-01-01',
            'interest_date'           => null,
            'book_date'               => null,
            'process_date'            => null,
            'order'                   => 0,
            'tag_count'               => 0,
            'encrypted'               => 0,
            'completed'               => 1,
        ];
    }
);

$factory->define(
    FireflyIII\Models\Bill::class,
    function (Faker\Generator $faker) {
        return [
            'id'              => $faker->numberBetween(1, 10),
            'user_id'         => 1,
            'name'            => $faker->words(3, true),
            'match'           => $faker->words(3, true),
            'amount_min'      => '100.00',
            'amount_max'      => '100.00',
            'date'            => '2017-01-01',
            'repeat_freq'     => 'monthly',
            'skip'            => 0,
            'automatch'       => 1,
            'name_encrypted'  => 0,
            'match_encrypted' => 0,
        ];
    }
);

$factory->define(
    FireflyIII\Models\PiggyBankRepetition::class,
    function (Faker\Generator $faker) {
        return [
            'id'            => $faker->unique()->numberBetween(100, 10000),
            'piggy_bank_id' => $faker->numberBetween(1, 10),
            'startdate'     => '2017-01-01',
            'targetdate'    => '2020-01-01',
            'currentamount' => 10,
        ];
    }
);

$factory->define(
    FireflyIII\Models\PiggyBank::class,
    function (Faker\Generator $faker) {
        return [
            'id'            => $faker->unique()->numberBetween(100, 10000),
            'account_id'    => $faker->numberBetween(1, 10),
            'name'          => $faker->words(3, true),
            'target_amount' => '1000.00',
            'startdate'     => '2017-01-01',
            'order'         => 1,
            'active'        => 1,
            'encrypted'     => 0,
        ];
    }
);

$factory->define(
    FireflyIII\Models\Tag::class,
    function (Faker\Generator $faker) {
        return [
            'id'      => $faker->unique()->numberBetween(200, 10000),
            'user_id' => 1,
            'tagMode' => 'nothing',
            'tag'     => $faker->words(1, true),
        ];
    }
);

$factory->define(
    FireflyIII\Models\Category::class,
    function (Faker\Generator $faker) {
        return [
            'id'   => $faker->numberBetween(1, 10),
            'name' => $faker->words(3, true),
        ];
    }
);

$factory->define(
    FireflyIII\Models\Budget::class,
    function (Faker\Generator $faker) {
        return [
            'id'   => $faker->numberBetween(1, 10),
            'name' => $faker->words(3, true),
        ];
    }
);

$factory->define(
    FireflyIII\Models\PiggyBankEvent::class,
    function (Faker\Generator $faker) {
        return [
            'id'                     => $faker->numberBetween(1, 10),
            'piggy_bank_id'          => $faker->numberBetween(1, 10),
            'transaction_journal_id' => $faker->numberBetween(1, 10),
            'date'                   => $faker->date('Y-m-d'),
            'amount'                 => '100',
        ];
    }
);

$factory->define(
    FireflyIII\Models\BudgetLimit::class,
    function (Faker\Generator $faker) {
        return [
            'id'         => $faker->numberBetween(1, 10),
            'start_date' => '2017-01-01',
            'end_date'   => '2017-01-31',
            'amount'     => '300',
            'budget_id'  => $faker->numberBetween(1, 6),
        ];
    }
);

$factory->define(
    FireflyIII\Models\Account::class,
    function (Faker\Generator $faker) {
        return [
            'id'              => $faker->unique()->numberBetween(1000, 10000),
            'name'            => $faker->words(3, true),
            'account_type_id' => 1,
            'active'          => true,
        ];
    }
);

$factory->define(
    FireflyIII\Models\Transaction::class,
    function (Faker\Generator $faker) {
        return [
            'transaction_amount'          => strval($faker->randomFloat(2, -100, 100)),
            'destination_amount'          => strval($faker->randomFloat(2, -100, 100)),
            'opposing_account_id'         => $faker->numberBetween(1, 10),
            'source_account_id'           => $faker->numberBetween(1, 10),
            'opposing_account_name'       => $faker->words(3, true),
            'description'                 => $faker->words(3, true),
            'source_account_name'         => $faker->words(3, true),
            'destination_account_id'      => $faker->numberBetween(1, 10),
            'date'                        => new Carbon,
            'destination_account_name'    => $faker->words(3, true),
            'amount'                      => strval($faker->randomFloat(2, -100, 100)),
            'budget_id'                   => 0,
            'category'                    => $faker->words(3, true),
            'transaction_journal_id'      => $faker->numberBetween(1, 10),
            'journal_id'                  => $faker->numberBetween(1, 10),
            'transaction_currency_code'   => 'EUR',
            'transaction_type_type'       => 'Withdrawal',
            'account_encrypted'           => 0,
            'account_name'                => 'Some name',
            'transaction_currency_id'     => 1,
            'transaction_currency_symbol' => '€',
            'foreign_destination_amount'  => null,
            'foreign_currency_id'         => null,
            'foreign_currency_code'       => null,
            'foreign_currency_symbol'     => null,
        ];
    }
);
