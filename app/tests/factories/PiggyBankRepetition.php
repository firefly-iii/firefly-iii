<?php

League\FactoryMuffin\Facade::define(
    'PiggyBankRepetition', [
                             'piggy_bank_id' => 'factory|PiggyBank',
                             'startdate'     => 'date|Y-m-d',
                             'targetdate'    => 'date|Y-m-d',
                             'currentamount' => function () {
                                 return rand(0, 100);
                             },

                         ]
);
