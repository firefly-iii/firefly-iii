<?php
use League\FactoryMuffin\Facade;

Facade::define(
      'AccountType',
          [
              'type'     => 'unique:word',
              'editable' => 1
          ]
);