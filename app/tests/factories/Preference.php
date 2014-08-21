<?php
use Carbon\Carbon;
use League\FactoryMuffin\Facade;

Facade::define(
    'Preference',
    [

        'user_id' => 'factory|User',
        'name'    => 'word',
        'data'    => 'word'
    ]
);