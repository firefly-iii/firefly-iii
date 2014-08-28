<?php
use League\FactoryMuffin\Facade;

Facade::define(
    'AccountType',
    [
        'description' => function () {
                $types = [
                    'Default account',
                    'Cash account',
                    'Initial balance account',
                    'Beneficiary account'
                ];

                return $types[rand(0, 3)];
            }
    ]
);