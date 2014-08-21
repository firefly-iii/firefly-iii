<?php
use League\FactoryMuffin\Facade;

Facade::define(
    'Category',
    [
        'name'    => 'word',
        'user_id' => 'factory|User',
        'class'   => 'Category'
    ]
);