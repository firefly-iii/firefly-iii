<?php

League\FactoryMuffin\Facade::define(
    'Transaction', [
                     'account_id'             => 'factory|Account',
                     'transaction_journal_id' => 'factory|TransactionJournal',
                     'description'            => 'sentence',
                     'amount'                 => 'numberBetween:1,100',
                 ]
);
