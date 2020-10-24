<?php
declare(strict_types=1);

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use FireflyIII\Models\Transaction;

$factory->define(Transaction::class, function (Faker $faker) {
    return [
        'transaction_journal_id' => 0,
        'account_id'             => 0,
        'amount'                 => 5,
    ];
});
