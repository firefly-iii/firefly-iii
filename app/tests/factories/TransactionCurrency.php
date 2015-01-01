<?php

League\FactoryMuffin\Facade::define(
    'TransactionCurrency', [
                             'code'   => function () {
                                 $code = '';
                                 for ($i = 0; $i < 3; $i++) {
                                     $code .= chr(rand(65, 90));
                                 }

                                 return $code;
                             },
                             'name'   => 'word',
                             'symbol' => '$'
                         ]
);
