<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use FireflyIII\Model;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;

$factory->define(TransactionJournal::class, function (Faker $faker) {
    return [
        'user_id'             => 1,
        'transaction_type_id' => 1,
        'description'         => $faker->words(3, true),
        'tag_count'           => 0,
        'date'                => $faker->date('Y-m-d'),
    ];
});

$factory->state(TransactionJournal::class, TransactionType::OPENING_BALANCE, function ($faker) {
    return [
        'transaction_type_id' => 4,
    ];
});

$factory->state(TransactionJournal::class, 'ob_broken', function ($faker) {
    return [
        'transaction_type_id' => 4,
    ];
});

$factory->afterCreatingState(TransactionJournal::class, TransactionType::OPENING_BALANCE, function ($journal, $faker) {
    $obAccount         = factory(Account::class)->state(AccountType::INITIAL_BALANCE)->create();
    $assetAccount      = factory(Account::class)->state(AccountType::ASSET)->create();
    $sourceTransaction = factory(Transaction::class)->create(
        [
            'account_id'             => $obAccount->id,
            'transaction_journal_id' => $journal->id,
            'amount'                 => '5',
        ]);

    $destTransaction = factory(Transaction::class)->create(
        [
            'account_id'             => $assetAccount->id,
            'transaction_journal_id' => $journal->id,
            'amount'                 => '-5',
        ]);
});

$factory->afterCreatingState(TransactionJournal::class, 'ob_broken', function ($journal, $faker) {
    $ob1 = factory(Account::class)->state(AccountType::INITIAL_BALANCE)->create();
    $ob2 = factory(Account::class)->state(AccountType::INITIAL_BALANCE)->create();

    $sourceTransaction = factory(Transaction::class)->create(
        [
            'account_id'             => $ob1->id,
            'transaction_journal_id' => $journal->id,
            'amount'                 => '5',
        ]);

    $destTransaction = factory(Transaction::class)->create(
        [
            'account_id'             => $ob2->id,
            'transaction_journal_id' => $journal->id,
            'amount'                 => '-5',
        ]);
});