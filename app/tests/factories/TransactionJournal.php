<?php


use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'TransactionJournal',
    [
        'transaction_type_id'     => 'factory|TransactionType',
        'transaction_currency_id' => 'factory|TransactionCurrency',
        'description'             => 'word',
        'completed'               => 'boolean',
        'user_id'                 => 'factory|User',
        'date'                    => new Carbon
    ]
);