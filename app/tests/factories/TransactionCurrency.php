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
                             'name'   => function () {
                                 $code = '';
                                 for ($i = 0; $i < 8; $i++) {
                                     $code .= chr(rand(65, 90));
                                 }

                                 return $code;
                             },
                             'symbol' => function () {
                                 $code = '';
                                 for ($i = 0; $i < 2; $i++) {
                                     $code .= chr(rand(65, 90));
                                 }

                                 return $code;
                             }
                         ]
);
