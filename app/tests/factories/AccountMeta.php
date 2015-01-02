<?php

League\FactoryMuffin\Facade::define(
    'AccountMeta', [
                     'account_id' => 'factory|Account',
                     'name'       => 'word',
                     'data'       => 'text'
                 ]
);
