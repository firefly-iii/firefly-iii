<?php

League\FactoryMuffin\Facade::define(
    'Transaction', [
                     'account_id'             => 'factory|Account',
                     'transaction_journal_id' => 'factory|TransactionJournal',
                     'description'            => 'sentence',
                     'amount'                 => function() {
                         return round(rand(100,10000) / 100,2);
                     }
                 ]
);
