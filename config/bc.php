<?php

return [
    'index'    => [
        'parent'        => null,
        'title'         => 'breadcrumbs.home',
        'static_route'  => 'home',
        'dynamic_route' => null,
    ],
    'accounts' => [
        'index' => [
            'parent'        => 'index',
            'title'         => 'breadcrumbs.accounts',
            'static_route'  => null,
            'dynamic_route' => 'accounts.index',
        ],
        'show' => [
            'parent' => 'accounts.index',
            'title'         => 'breadcrumbs.accounts_show',
            'static_route'  => null,
            'dynamic_route' => 'accounts.show',
        ],
    ],
];