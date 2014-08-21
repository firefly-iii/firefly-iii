<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'PiggybankEvent',
    [

        'piggybank_id' => 'factory|Piggybank',
        'date'         => new Carbon,
        'amount'       => 10

    ]
);