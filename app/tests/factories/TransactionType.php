<?php


use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'TransactionType',
    [
        'type' => function() {
            $types = ['Withdrawal','Deposit','Transfer','Opening balance'];
            return $types[rand(0,3)];
        }
    ]
);