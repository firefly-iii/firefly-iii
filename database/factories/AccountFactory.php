<?php
declare(strict_types=1);

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;

$factory->define(Account::class, function (Faker $faker) {
    return [
        'user_id'         => 1,
        'account_type_id' => 1,
        'name'            => $faker->words(3, true),
        'virtual_balance' => '0',
        'active'          => 1,
        'encrypted'       => 0,
        'order'           => 1,
    ];
});

$factory->state(Account::class, AccountType::ASSET, function ($faker) {
    return [
        'account_type_id' => 3,
    ];
});

$factory->state(Account::class, AccountType::INITIAL_BALANCE, function ($faker) {
    return [
        'account_type_id' => 6,
    ];
});

$factory->state(Account::class, AccountType::EXPENSE, function ($faker) {
    return [
        'account_type_id' => 4,
    ];
});