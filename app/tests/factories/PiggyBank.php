<?php

League\FactoryMuffin\Facade::define(
    'PiggyBank', [
                   'account_id'    => 'factory|Account',
                   'name'          => 'word',
                   'targetamount'  => function () {
                       return rand(1, 400);
                   },
                   'startdate'     => 'date|Y-m-d',
                   'targetdate'    => 'date|Y-m-d',
                   'repeats'       => 'boolean',
                   'rep_length'    => function () {
                       $set = ['day', 'week', 'quarter', 'month', 'year'];

                       return $set[rand(0, count($set) - 1)];
                   },
                   'rep_every'     => function() {return rand(0,3);},
                   'rep_times'     => function() {return rand(0,3);},
                   'reminder'      => function () {
                       $set = ['day', 'week', 'quarter', 'month', 'year'];

                       return $set[rand(0, count($set) - 1)];
                   },
                   'reminder_skip' => function () {
                       return rand(0, 3);
                   },
                   'remind_me'     => 'boolean',
                   'order'         => function () {
                       return rand(0, 10);
                   },

               ]
);
