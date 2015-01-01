<?php

League\FactoryMuffin\Facade::define(
    'PiggyBank', [
                   'account_id'    => 'factory|Account',
                   'name'          => 'word',
                   'targetamount'  => 'numberBetween|10;400',
                   'startdate'     => 'date|Y-m-d',
                   'targetdate'    => 'date|Y-m-d',
                   'repeats'       => 'boolean',
                   'rep_length'    => function () {
                       $set = ['day', 'week', 'quarter', 'month', 'year'];

                       return $set[rand(0, count($set) - 1)];
                   },
                   'rep_every'     => 'numberBetween:0,3',
                   'rep_times'     => 'numberBetween:0,3',
                   'reminder'      => function () {
                       $set = ['day', 'week', 'quarter', 'month', 'year'];

                       return $set[rand(0, count($set) - 1)];
                   },
                   'reminder_skip' => 'numberBetween:0,3',
                   'remind_me'     => 'boolean',
                   'order'         => 'numberBetween:0,10',

               ]
);
