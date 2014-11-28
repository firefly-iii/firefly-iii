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
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push('Delete ' . $account->name, route('accounts.delete', $account->id));
    }
);

Breadcrumbs::register(
    'accounts.edit', function (Generator $breadcrumbs, \Account $account) {
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push('Edit ' . $account->name, route('accounts.edit', $account->id));
    }
);

// budgets.
Breadcrumbs::register(
    'budgets.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Budgets', route('budgets.index'));
    }
);
Breadcrumbs::register(
    'budgets.create', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push('Create new budget', route('budgets.create'));
    }
);

Breadcrumbs::register(
    'budgets.edit', function (Generator $breadcrumbs, Budget $budget) {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push('Edit '.$budget->name, route('budgets.edit',$budget->id));
    }
);
Breadcrumbs::register(
    'budgets.delete', function (Generator $breadcrumbs, Budget $budget) {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push('Delete '.$budget->name, route('budgets.delete',$budget->id));
    }
);

Breadcrumbs::register(
    'budgets.show', function (Generator $breadcrumbs, Budget $budget, LimitRepetition $repetition = null) {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push($budget->name, route('budgets.show', $budget->id));
        if (!is_null($repetition)) {
            $breadcrumbs->push(
                DateKit::periodShow($repetition->startdate, $repetition->limit->repeat_freq), route('budgets.show', $budget->id, $repetition->id)
            );
        }
        //$breadcrumbs->push('Budgets', route('budgets.index'));
    }
);