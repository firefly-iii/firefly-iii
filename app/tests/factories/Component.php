<?php
use League\FactoryMuffin\Facade;

Facade::define(
    'Component',
    [
        'name'    => 'word',
        'user_id' => 'factory|User',
        'class'   => 'Component'
    ]
);