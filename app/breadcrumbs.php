<?php
use DaveJamesMiller\Breadcrumbs\Generator;
use FireflyIII\Exception\FireflyException;

/*
 * Back home.
 */
Breadcrumbs::register(
    'home',
    function (Generator $breadcrumbs) {

        $breadcrumbs->push('Home', route('index'));
    }
);

// accounts
Breadcrumbs::register(
    'accounts.index', function (Generator $breadcrumbs, $what) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(ucfirst($what) . ' accounts', route('accounts.index', $what));
    }
);
Breadcrumbs::register(
    'accounts.show', function (Generator $breadcrumbs, \Account $account) {
        switch ($account->accountType->type) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($account->accountType->type) . '"');
                break;
            case 'Default account':
            case 'Asset account':
                $what = 'asset';
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $what = 'expense';
                break;
            case 'Revenue account':
                $what = 'revenue';
                break;
        }
        $breadcrumbs->parent('accounts.index', $what);
        $breadcrumbs->push($account->name, route('accounts.show', $account->id));
    }
);
Breadcrumbs::register(
    'accounts.delete', function (Generator $breadcrumbs, \Account $account) {
        switch ($account->accountType->type) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($account->accountType->type) . '"');
                break;
            case 'Default account':
            case 'Asset account':
                $what = 'asset';
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $what = 'expense';
                break;
            case 'Revenue account':
                $what = 'revenue';
                break;
        }
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push('Delete ' . $account->name, route('accounts.delete', $account->id));
    }
);

Breadcrumbs::register(
    'accounts.edit', function (Generator $breadcrumbs, \Account $account) {
        switch ($account->accountType->type) {
            default:
                throw new FireflyException('Cannot handle account type "' . e($account->accountType->type) . '"');
                break;
            case 'Default account':
            case 'Asset account':
                $what = 'asset';
                break;
            case 'Expense account':
            case 'Beneficiary account':
                $what = 'expense';
                break;
            case 'Revenue account':
                $what = 'revenue';
                break;
        }
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push('Edit ' . $account->name, route('accounts.edit', $account->id));
    }
);