<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'Limit',
    [

        'component_id' => 'factory|Budget',
        'startdate' => function () {
            $start = new Carbon;
            $start->startOfMonth();
            return $start;
        },
        'amount' => 100,
        'repeats' => 'boolean',
        'repeat_freq' => function(){
            $frequencies = ['daily','weekly','monthly','quarterly','half-year','yearly'];
            return $frequencies[rand(0,5)];
        }


    ]
);