<?php
use Carbon\Carbon;
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
                DateKit::periodShow($repetition->startdate, $repetition->budgetlimit->repeat_freq), route('budgets.show', $budget->id, $repetition->id)
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
    'piggyBanks.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Piggy banks', route('piggyBanks.index'));
    }
);
Breadcrumbs::register(
    'piggyBanks.create', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('piggyBanks.index');
        $breadcrumbs->push('Create new piggy bank', route('piggyBanks.create'));
    }
);

Breadcrumbs::register(
    'piggyBanks.edit', function (Generator $breadcrumbs, Piggybank $piggyBank) {
        $breadcrumbs->parent('piggyBanks.show', $piggyBank);
        $breadcrumbs->push('Edit ' . $piggyBank->name, route('piggyBanks.edit', $piggyBank->id));
    }
);
Breadcrumbs::register(
    'piggyBanks.delete', function (Generator $breadcrumbs, Piggybank $piggyBank) {
        $breadcrumbs->parent('piggyBanks.show', $piggyBank);
        $breadcrumbs->push('Delete ' . $piggyBank->name, route('piggyBanks.delete', $piggyBank->id));
    }
);

Breadcrumbs::register(
    'piggyBanks.show', function (Generator $breadcrumbs, Piggybank $piggyBank) {
        $breadcrumbs->parent('piggyBanks.index');
        $breadcrumbs->push($piggyBank->name, route('piggyBanks.show', $piggyBank->id));

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
        $breadcrumbs->push('Edit ' . $recurring->name, route('recurring.edit', $recurring->id));
    }
);
Breadcrumbs::register(
    'recurring.delete', function (Generator $breadcrumbs, RecurringTransaction $recurring) {
        $breadcrumbs->parent('recurring.show', $recurring);
        $breadcrumbs->push('Delete ' . $recurring->name, route('recurring.delete', $recurring->id));
    }
);

Breadcrumbs::register(
    'recurring.show', function (Generator $breadcrumbs, RecurringTransaction $recurring) {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push($recurring->name, route('recurring.show', $recurring->id));

    }
);

// reminders
Breadcrumbs::register(
    'reminders.show', function (Generator $breadcrumbs, Reminder $reminder) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Reminder #' . $reminder->id, route('reminders.show', $reminder->id));

    }
);

// repeated expenses
Breadcrumbs::register(
    'repeated.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Repeated expenses', route('repeated.index'));
    }
);
Breadcrumbs::register(
    'repeated.create', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('repeated.index');
        $breadcrumbs->push('Create new repeated expense', route('repeated.create'));
    }
);

Breadcrumbs::register(
    'repeated.edit', function (Generator $breadcrumbs, Piggybank $piggyBank) {
        $breadcrumbs->parent('repeated.show', $piggyBank);
        $breadcrumbs->push('Edit ' . $piggyBank->name, route('repeated.edit', $piggyBank->id));
    }
);
Breadcrumbs::register(
    'repeated.delete', function (Generator $breadcrumbs, Piggybank $piggyBank) {
        $breadcrumbs->parent('repeated.show', $piggyBank);
        $breadcrumbs->push('Delete ' . $piggyBank->name, route('repeated.delete', $piggyBank->id));
    }
);

Breadcrumbs::register(
    'repeated.show', function (Generator $breadcrumbs, Piggybank $piggyBank) {
        $breadcrumbs->parent('repeated.index');
        $breadcrumbs->push($piggyBank->name, route('repeated.show', $piggyBank->id));

    }
);

// reports
Breadcrumbs::register(
    'reports.index', function (Generator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Reports', route('reports.index'));
    }
);

Breadcrumbs::register(
    'reports.year', function (Generator $breadcrumbs, Carbon $date) {
        $breadcrumbs->parent('reports.index');
        $breadcrumbs->push($date->format('Y'), route('reports.year', $date->format('Y')));
    }
);
Breadcrumbs::register(
    'reports.budgets', function (Generator $breadcrumbs, Carbon $date) {
        $breadcrumbs->parent('reports.index');
        $breadcrumbs->push('Budgets in ' . $date->format('F Y'), route('reports.budgets', $date->format('Y')));
    }
);
Breadcrumbs::register(
    'reports.unbalanced', function (Generator $breadcrumbs, Carbon $date) {
        $breadcrumbs->parent('reports.index');
        $breadcrumbs->push('Unbalanced transactions in ' . $date->format('F Y'), route('reports.unbalanced', $date->format('Y')));
    }
);
// search
Breadcrumbs::register(
    'search', function (Generator $breadcrumbs, $query) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push('Search for "' . e($query) . '"', route('search'));
    }
);

// transactions
Breadcrumbs::register(
    'transactions.index', function (Generator $breadcrumbs, $what) {
        $breadcrumbs->parent('home');

        switch ($what) {
            case 'expenses':
            case 'withdrawal':
                $subTitle = 'Expenses';
                break;
            case 'revenue':
            case 'deposit':
                $subTitle = 'Revenue, income and deposits';
                break;
            case 'transfer':
            case 'transfers':
                $subTitle = 'Transfers';
                break;
            case 'opening balance':
                $subTitle = 'Opening balances';
                break;
            default:
                throw new FireflyException('Cannot handle $what "'.e($what).'" in bread crumbs');
        }

        $breadcrumbs->push($subTitle, route('transactions.index', $what));
    }
);
Breadcrumbs::register(
    'transactions.create', function (Generator $breadcrumbs, $what) {
        $breadcrumbs->parent('transactions.index', $what);
        $breadcrumbs->push('Create new ' . $what, route('transactions.create', $what));
    }
);

Breadcrumbs::register(
    'transactions.edit', function (Generator $breadcrumbs, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push('Edit ' . $journal->description, route('transactions.edit', $journal ->id));
    }
);
Breadcrumbs::register(
    'transactions.delete', function (Generator $breadcrumbs, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push('Delete ' . $journal->description, route('transactions.delete', $journal->id));
    }
);

Breadcrumbs::register(
    'transactions.show', function (Generator $breadcrumbs, TransactionJournal $journal) {

        $breadcrumbs->parent('transactions.index', strtolower($journal->transactionType->type));
        $breadcrumbs->push($journal->description, route('transactions.show', $journal->id));

    }
);