<?php
use League\FactoryMuffin\Facade;

Facade::define(
    'Budget',
    [
        'name'    => 'word',
        'user_id' => 'factory|User',
        'class'   => 'Category'
    ]
);