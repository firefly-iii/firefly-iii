<?php

League\FactoryMuffin\Facade::define(
    'Reminder', [
                  'user_id'            => 'factory|User',
                  'startdate'          => 'date|Y-m-d',
                  'enddate'            => 'date|Y-m-d',
                  'active'             => 'boolean',
                  'notnow'             => 'boolean',
                  'remindersable_id'   => 0,
                  'remindersable_type' => '',
              ]
);
