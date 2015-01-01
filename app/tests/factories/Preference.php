<?php

League\FactoryMuffin\Facade::define(
    'Preference', [
                     'user_id' => 'factory|User',
                     'name'    => 'word',
                     'data'    => 'sentence',
                 ]
);
