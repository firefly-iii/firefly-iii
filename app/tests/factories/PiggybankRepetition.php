<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'PiggybankRepetition',
    [


        'piggybank_id'  => 'factory|Piggybank',
        'startdate'     => function () {
                $start = new Carbon;
                $start->startOfMonth();

                return $start;
            },
        'targetdate'    => function () {
                $end = new Carbon;
                $end->endOfMonth();

                return $end;
            },
        'currentamount' => 200
    ]
);