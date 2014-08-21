<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'Piggybank',
    [

        'account_id' => 'factory|Account',
        'name' => 'string',
        'targetamount' => 'integer',
        'startdate' => function () {
            $start = new Carbon;
            $start->startOfMonth();
            return $start;
        },
        'targetdate' => function () {
            $end = new Carbon;
            $end->endOfMonth();
            return $end;
        },
        'repeats' => 0,
        'rep_length' => null,
        'rep_times' => 0,
        'rep_every' => 0,
        'reminder' => null,
        'reminder_skip' => 0,
        'order' => 1,
    ]
);