<?php

League\FactoryMuffin\Facade::define(
    'PiggyBankRepetition', [
                             'piggy_bank_id' => 'factory|PiggyBank',
                             'startdate'     => 'date|Y-m-d',
                             'targetdate'    => 'date|Y-m-d',
                             'currentamount' => 'numberBetween:0,100',

                         ]
);
