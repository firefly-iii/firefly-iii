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
        $breadcrumbs->push('Edit ' . $budget->name, route('budgets.edit', $budget->id));
    }
);
Breadcrumbs::register(
    'budgets.delete', function (Generator $breadcrumbs, Budget $budget) {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push('Delete ' . $budget->name, route('budgets.delete', $budget->id));
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
    }
);

// categories
Breadcrumbs::register(
    'categories.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Categories', route('categories.index'));
    }
);
Breadcrumbs::register(
    'categories.create', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push('Create new category', route('categories.create'));
    }
);

Breadcrumbs::register(
    'categories.edit', function (Generator $breadcrumbs, Category $category) {
        $breadcrumbs->parent('categories.show', $category);
        $breadcrumbs->push('Edit ' . $category->name, route('categories.edit', $category->id));
    }
);
Breadcrumbs::register(
    'categories.delete', function (Generator $breadcrumbs, Category $category) {
        $breadcrumbs->parent('categories.show', $category);
        $breadcrumbs->push('Delete ' . $category->name, route('categories.delete', $category->id));
    }
);

Breadcrumbs::register(
    'categories.show', function (Generator $breadcrumbs, Category $category) {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push($category->name, route('categories.show', $category->id));

    }
);


// piggy banks
Breadcrumbs::register(
    'piggybanks.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Piggy banks', route('piggybanks.index'));
    }
);
Breadcrumbs::register(
    'piggybanks.create', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('piggybanks.index');
        $breadcrumbs->push('Create new piggy bank', route('piggybanks.create'));
    }
);

Breadcrumbs::register(
    'piggybanks.edit', function (Generator $breadcrumbs, Piggybank $piggybank) {
        $breadcrumbs->parent('piggybanks.show', $piggybank);
        $breadcrumbs->push('Edit ' . $piggybank->name, route('piggybanks.edit', $piggybank->id));
    }
);
Breadcrumbs::register(
    'piggybanks.delete', function (Generator $breadcrumbs, Piggybank $piggybank) {
        $breadcrumbs->parent('piggybanks.show', $piggybank);
        $breadcrumbs->push('Delete ' . $piggybank->name, route('piggybanks.delete', $piggybank->id));
    }
);

Breadcrumbs::register(
    'piggybanks.show', function (Generator $breadcrumbs, Piggybank $piggybank) {
        $breadcrumbs->parent('piggybanks.index');
        $breadcrumbs->push($piggybank->name, route('piggybanks.show', $piggybank->id));

    }
);
// preferences
Breadcrumbs::register(
    'preferences', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Preferences', route('preferences'));

    }
);

// profile
Breadcrumbs::register(
    'profile', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Profile', route('profile'));

    }
);
Breadcrumbs::register(
    'change-password', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('profile');
        $breadcrumbs->push('Change your password', route('change-password'));

    }
);

// recurring transactions
Breadcrumbs::register(
    'recurring.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Recurring transactions', route('recurring.index'));
    }
);
Breadcrumbs::register(
    'recurring.create', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push('Create new recurring transaction', route('recurring.create'));
    }
);

Breadcrumbs::register(
    'recurring.edit', function (Generator $breadcrumbs, RecurringTransaction $recurring) {
        $breadcrumbs->parent('recurring.show', $recurring);
        $breadcrumbs->push('Edit '.$recurring->name, route('recurring.edit',$recurring->id));
    }
);
Breadcrumbs::register(
    'recurring.delete', function (Generator $breadcrumbs, RecurringTransaction $recurring) {
        $breadcrumbs->parent('recurring.show', $recurring);
        $breadcrumbs->push('Delete '.$recurring->name, route('recurring.delete',$recurring->id));
    }
);

Breadcrumbs::register(
    'recurring.show', function (Generator $breadcrumbs, RecurringTransaction $recurring) {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push($recurring->name, route('recurring.show', $recurring->id));

    }
);