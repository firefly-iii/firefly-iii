<?php

League\FactoryMuffin\Facade::define(
    'Account', [
                 'user_id'         => 'factory|User',
                 'account_type_id' => 'factory|AccountType',
                 'name'            => 'word',
                 'active'          => 'boolean',
             ]
);
