<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'LimitRepetition',
    [

        'limit_id' => 'factory|Limit',
        'startdate' => function () {
            $start = new Carbon;
            $start->startOfMonth();
            return $start;

        },
        'enddate' => function () {
            $end = new Carbon;
            $end->endOfMonth();
            return $end;

        },
        'amount' => 100


    ]
);