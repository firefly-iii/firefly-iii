<?php


use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'Transaction',
    [
        'account_id'             => 'factory|Account',
        'piggybank_id'           => null,
        'transaction_journal_id' => 'factory|TransactionJournal',
        'description'            => 'string',
        'amount'                 => 'integer:5',
    ]
);