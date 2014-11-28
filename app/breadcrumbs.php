<?php
use DaveJamesMiller\Breadcrumbs\Generator;

/*
 * Back home.
 */
Breadcrumbs::register(
    'home',
    function (Generator $breadcrumbs) {

        $breadcrumbs->push('Home', route('index'));
    }
);

Breadcrumbs::register(
    'accounts.index', function (Generator $breadcrumbs, $what) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(ucfirst($what) . ' accounts', route('accounts.index', $what));
    }
);
Breadcrumbs::register(
    'accounts.show', function (Generator $breadcrumbs, $what, \Account $account) {
        $breadcrumbs->parent('accounts.index',$what);
        $breadcrumbs->push($account->name, route('accounts.show', $account->id));
    }
);