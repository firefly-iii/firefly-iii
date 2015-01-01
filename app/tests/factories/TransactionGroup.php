<?php

League\FactoryMuffin\Facade::define(
    'TransactionGroup', [
                          'user_id'  => 'factory|User',
                          'relation' => 'balance',
                      ]
);
