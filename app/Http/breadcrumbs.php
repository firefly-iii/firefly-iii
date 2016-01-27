<?php
use Carbon\Carbon;
use DaveJamesMiller\Breadcrumbs\Generator as BreadCrumbGenerator;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;

/*
 * Back home.
 */
Breadcrumbs::register(
    'home',
    function (BreadCrumbGenerator $breadcrumbs) {

        $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
    }
);

Breadcrumbs::register(
    'index',
    function (BreadCrumbGenerator $breadcrumbs) {

        $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
    }
);
//trans('breadcrumbs.')

// accounts
Breadcrumbs::register(
    'accounts.index', function (BreadCrumbGenerator $breadcrumbs, $what) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.' . strtolower(e($what)) . '_accounts'), route('accounts.index', [$what]));
}
);

Breadcrumbs::register(
    'accounts.create', function (BreadCrumbGenerator $breadcrumbs, $what) {
    $breadcrumbs->parent('accounts.index', $what);
    $breadcrumbs->push(trans('firefly.new_' . strtolower(e($what)) . '_account'), route('accounts.create', [$what]));
}
);

Breadcrumbs::register(
    'accounts.show', function (BreadCrumbGenerator $breadcrumbs, Account $account) {

    $what = Config::get('firefly.shortNamesByFullName.' . $account->accountType->type);


    $breadcrumbs->parent('accounts.index', $what);
    $breadcrumbs->push(e($account->name), route('accounts.show', [$account->id]));
}
);
Breadcrumbs::register(
    'accounts.delete', function (BreadCrumbGenerator $breadcrumbs, Account $account) {
    $breadcrumbs->parent('accounts.show', $account);
    $breadcrumbs->push(trans('firefly.delete_account', ['name' => e($account->name)]), route('accounts.delete', [$account->id]));
}
);


Breadcrumbs::register(
    'accounts.edit', function (BreadCrumbGenerator $breadcrumbs, Account $account) {
    $breadcrumbs->parent('accounts.show', $account);
    $what = Config::get('firefly.shortNamesByFullName.' . $account->accountType->type);

    $breadcrumbs->push(trans('firefly.edit_' . $what . '_account', ['name' => e($account->name)]), route('accounts.edit', [$account->id]));
}
);

// budgets.
Breadcrumbs::register(
    'budgets.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.budgets'), route('budgets.index'));
}
);
Breadcrumbs::register(
    'budgets.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('budgets.index');
    $breadcrumbs->push(trans('firefly.create_new_budget'), route('budgets.create'));
}
);

Breadcrumbs::register(
    'budgets.edit', function (BreadCrumbGenerator $breadcrumbs, Budget $budget) {
    $breadcrumbs->parent('budgets.show', $budget);
    $breadcrumbs->push(trans('firefly.edit_budget', ['name' => e($budget->name)]), route('budgets.edit', [$budget->id]));
}
);
Breadcrumbs::register(
    'budgets.delete', function (BreadCrumbGenerator $breadcrumbs, Budget $budget) {
    $breadcrumbs->parent('budgets.show', $budget);
    $breadcrumbs->push(trans('firefly.delete_budget', ['name' => e($budget->name)]), route('budgets.delete', [$budget->id]));
}
);

Breadcrumbs::register(
    'budgets.noBudget', function (BreadCrumbGenerator $breadcrumbs, $subTitle) {
    $breadcrumbs->parent('budgets.index');
    $breadcrumbs->push($subTitle, route('budgets.noBudget'));
}
);

Breadcrumbs::register(
    'budgets.show', function (BreadCrumbGenerator $breadcrumbs, Budget $budget, LimitRepetition $repetition = null) {
    $breadcrumbs->parent('budgets.index');
    $breadcrumbs->push(e($budget->name), route('budgets.show', [$budget->id]));
    if (!is_null($repetition) && !is_null($repetition->id)) {
        $breadcrumbs->push(
            Navigation::periodShow($repetition->startdate, $repetition->budgetLimit->repeat_freq), route('budgets.show', [$budget->id, $repetition->id])
        );
    }
}
);

// categories
Breadcrumbs::register(
    'categories.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.categories'), route('categories.index'));
}
);
Breadcrumbs::register(
    'categories.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('categories.index');
    $breadcrumbs->push(trans('firefly.new_category'), route('categories.create'));
}
);

Breadcrumbs::register(
    'categories.edit', function (BreadCrumbGenerator $breadcrumbs, Category $category) {
    $breadcrumbs->parent('categories.show', $category);
    $breadcrumbs->push(trans('firefly.edit_category', ['name' => e($category->name)]), route('categories.edit', [$category->id]));
}
);
Breadcrumbs::register(
    'categories.delete', function (BreadCrumbGenerator $breadcrumbs, Category $category) {
    $breadcrumbs->parent('categories.show', $category);
    $breadcrumbs->push(trans('firefly.delete_category', ['name' => e($category->name)]), route('categories.delete', [$category->id]));
}
);

Breadcrumbs::register(
    'categories.show', function (BreadCrumbGenerator $breadcrumbs, Category $category) {
    $breadcrumbs->parent('categories.index');
    $breadcrumbs->push(e($category->name), route('categories.show', [$category->id]));

}
);

Breadcrumbs::register(
    'categories.show.date', function (BreadCrumbGenerator $breadcrumbs, Category $category, Carbon $date) {

    // get current period preference.
    $range = Preferences::get('viewRange', '1M')->data;

    $breadcrumbs->parent('categories.index');
    $breadcrumbs->push(e($category->name), route('categories.show', [$category->id]));
    $breadcrumbs->push(Navigation::periodShow($date, $range), route('categories.show.date', [$category->id, $date->format('Y-m-d')]));

}
);

Breadcrumbs::register(
    'categories.noCategory', function (BreadCrumbGenerator $breadcrumbs, $subTitle) {
    $breadcrumbs->parent('categories.index');
    $breadcrumbs->push($subTitle, route('categories.noCategory'));
}
);

// CSV:
Breadcrumbs::register(
    'csv.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.csv_index_title'), route('csv.index'));
}
);

Breadcrumbs::register(
    'csv.column-roles', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('csv.index');
    $breadcrumbs->push(trans('firefly.csv_define_column_roles'), route('csv.column-roles'));
}
);

Breadcrumbs::register(
    'csv.map', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('csv.index');
    $breadcrumbs->push(trans('firefly.csv_map_values'), route('csv.map'));
}
);

Breadcrumbs::register(
    'csv.download-config-page', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('csv.index');
    $breadcrumbs->push(trans('firefly.csv_download_config'), route('csv.download-config-page'));
}
);

Breadcrumbs::register(
    'csv.process', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('csv.index');
    $breadcrumbs->push(trans('firefly.csv_process_title'), route('csv.process'));
}
);


// currencies.
Breadcrumbs::register(
    'currency.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.currencies'), route('currency.index'));
}
);

Breadcrumbs::register(
    'currency.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('currency.index');
    $breadcrumbs->push(trans('firefly.create_currency'), route('currency.create'));
}
);

Breadcrumbs::register(
    'currency.edit', function (BreadCrumbGenerator $breadcrumbs, TransactionCurrency $currency) {
    $breadcrumbs->parent('currency.index');
    $breadcrumbs->push(trans('breadcrumbs.edit_currency', ['name' => e($currency->name)]), route('currency.edit', [$currency->id]));
}
);
Breadcrumbs::register(
    'currency.delete', function (BreadCrumbGenerator $breadcrumbs, TransactionCurrency $currency) {
    $breadcrumbs->parent('currency.index');
    $breadcrumbs->push(trans('breadcrumbs.delete_currency', ['name' => e($currency->name)]), route('currency.delete', [$currency->id]));
}
);


// piggy banks
Breadcrumbs::register(
    'piggy-banks.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.piggyBanks'), route('piggy-banks.index'));
}
);
Breadcrumbs::register(
    'piggy-banks.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('piggy-banks.index');
    $breadcrumbs->push(trans('breadcrumbs.newPiggyBank'), route('piggy-banks.create'));
}
);

Breadcrumbs::register(
    'piggy-banks.edit', function (BreadCrumbGenerator $breadcrumbs, PiggyBank $piggyBank) {
    $breadcrumbs->parent('piggy-banks.show', $piggyBank);
    $breadcrumbs->push(trans('breadcrumbs.edit_piggyBank', ['name' => e($piggyBank->name)]), route('piggy-banks.edit', [$piggyBank->id]));
}
);
Breadcrumbs::register(
    'piggy-banks.delete', function (BreadCrumbGenerator $breadcrumbs, PiggyBank $piggyBank) {
    $breadcrumbs->parent('piggy-banks.show', $piggyBank);
    $breadcrumbs->push(trans('firefly.delete_piggy_bank', ['name' => e($piggyBank->name)]), route('piggy-banks.delete', [$piggyBank->id]));
}
);

Breadcrumbs::register(
    'piggy-banks.show', function (BreadCrumbGenerator $breadcrumbs, PiggyBank $piggyBank) {
    $breadcrumbs->parent('piggy-banks.index');
    $breadcrumbs->push(e($piggyBank->name), route('piggy-banks.show', [$piggyBank->id]));

}
);

// preferences
Breadcrumbs::register(
    'preferences', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences'));

}
);

// profile
Breadcrumbs::register(
    'profile', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile'));

}
);
Breadcrumbs::register(
    'profile.change-password', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('profile');
    $breadcrumbs->push(trans('breadcrumbs.changePassword'), route('profile.change-password'));

}
);
Breadcrumbs::register(
    'profile.delete-account', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('profile');
    $breadcrumbs->push(trans('firefly.delete_account'), route('profile.delete-account'));

}
);

// bills
Breadcrumbs::register(
    'bills.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.bills'), route('bills.index'));
}
);
Breadcrumbs::register(
    'bills.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('bills.index');
    $breadcrumbs->push(trans('breadcrumbs.newBill'), route('bills.create'));
}
);

Breadcrumbs::register(
    'bills.edit', function (BreadCrumbGenerator $breadcrumbs, Bill $bill) {
    $breadcrumbs->parent('bills.show', $bill);
    $breadcrumbs->push(trans('breadcrumbs.edit_bill', ['name' => e($bill->name)]), route('bills.edit', [$bill->id]));
}
);
Breadcrumbs::register(
    'bills.delete', function (BreadCrumbGenerator $breadcrumbs, Bill $bill) {
    $breadcrumbs->parent('bills.show', $bill);
    $breadcrumbs->push(trans('breadcrumbs.delete_bill', ['name' => e($bill->name)]), route('bills.delete', [$bill->id]));
}
);

Breadcrumbs::register(
    'bills.show', function (BreadCrumbGenerator $breadcrumbs, Bill $bill) {
    $breadcrumbs->parent('bills.index');
    $breadcrumbs->push(e($bill->name), route('bills.show', [$bill->id]));

}
);

// reports
Breadcrumbs::register(
    'reports.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.reports'), route('reports.index'));
}
);

Breadcrumbs::register(
    'reports.report', function (BreadCrumbGenerator $breadcrumbs, Carbon $start, Carbon $end, $reportType, $accountIds) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $title       = (string)trans('firefly.report_' . $reportType, ['start' => $start->formatLocalized($monthFormat), 'end' => $end->formatLocalized($monthFormat)]);

    $breadcrumbs->push($title, route('reports.report', [$reportType, $start->format('Ymd'), $end->format('Ymd'), $accountIds]));
}
);

/**
 * Rules
 */
Breadcrumbs::register(
    'rules.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.rules'), route('rules.index'));
}
);

// search
Breadcrumbs::register(
    'search', function (BreadCrumbGenerator $breadcrumbs, $query) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.searchResult', ['query' => e($query)]), route('search'));
}
);

// transactions
Breadcrumbs::register(
    'transactions.index', function (BreadCrumbGenerator $breadcrumbs, $what) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));
}
);
Breadcrumbs::register(
    'transactions.create', function (BreadCrumbGenerator $breadcrumbs, $what) {
    $breadcrumbs->parent('transactions.index', $what);
    $breadcrumbs->push(trans('breadcrumbs.create_' . e($what)), route('transactions.create', [$what]));
}
);

Breadcrumbs::register(
    'transactions.edit', function (BreadCrumbGenerator $breadcrumbs, TransactionJournal $journal) {
    $breadcrumbs->parent('transactions.show', $journal);
    $breadcrumbs->push(trans('breadcrumbs.edit_journal', ['description' => $journal->description]), route('transactions.edit', [$journal->id]));
}
);
Breadcrumbs::register(
    'transactions.delete', function (BreadCrumbGenerator $breadcrumbs, TransactionJournal $journal) {
    $breadcrumbs->parent('transactions.show', $journal);
    $breadcrumbs->push(trans('breadcrumbs.delete_journal', ['description' => e($journal->description)]), route('transactions.delete', [$journal->id]));
}
);

Breadcrumbs::register(
    'transactions.show', function (BreadCrumbGenerator $breadcrumbs, TransactionJournal $journal) {

    $breadcrumbs->parent('transactions.index', strtolower($journal->getTransactionType()));
    $breadcrumbs->push($journal->description, route('transactions.show', [$journal->id]));

}
);

// tags
Breadcrumbs::register(
    'tags.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.tags'), route('tags.index'));
}
);

Breadcrumbs::register(
    'tags.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('tags.index');
    $breadcrumbs->push(trans('breadcrumbs.createTag'), route('tags.create'));
}
);

Breadcrumbs::register(
    'tags.edit', function (BreadCrumbGenerator $breadcrumbs, Tag $tag) {
    $breadcrumbs->parent('tags.show', $tag);
    $breadcrumbs->push(trans('breadcrumbs.edit_tag', ['tag' => e($tag->tag)]), route('tags.edit', [$tag->id]));
}
);

Breadcrumbs::register(
    'tags.delete', function (BreadCrumbGenerator $breadcrumbs, Tag $tag) {
    $breadcrumbs->parent('tags.show', $tag);
    $breadcrumbs->push(trans('breadcrumbs.delete_tag', ['tag' => e($tag->tag)]), route('tags.delete', [$tag->id]));
}
);


Breadcrumbs::register(
    'tags.show', function (BreadCrumbGenerator $breadcrumbs, Tag $tag) {
    $breadcrumbs->parent('tags.index');
    $breadcrumbs->push(e($tag->tag), route('tags.show', [$tag->id]));
}
);
