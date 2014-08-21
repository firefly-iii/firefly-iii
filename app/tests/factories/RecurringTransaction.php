<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

// TODO better factory.

Facade::define(
    'RecurringTransaction',
    [

        'user_id' => 'factory|User',
        'name' => 'string',
        'match' => 'string',
        'amount_max' => 100,
        'amount_min' => 50,
        'date' => new Carbon,
        'active' => 'boolean',
        'automatch' => 'boolean',
        'repeat_freq' => 'monthly',
        'skip' => 'boolean',

    ]
);