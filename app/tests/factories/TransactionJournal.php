<?php

League\FactoryMuffin\Facade::define(
    'TransactionJournal', [
        'transaction_type_id'     => 'factory|TransactionType',
        'transaction_currency_id' => 'factory|TransactionCurrency',
        'description'             => 'text',
        'date'                    => 'date|Y-m-d',
        'completed'               => 'boolean',
        'user_id'                 => 'factory|User'
    ]
);
