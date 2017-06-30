<?php
/**
 * breadcrumbs.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);
use Carbon\Carbon;
use DaveJamesMiller\Breadcrumbs\Generator as BreadCrumbGenerator;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * HOME
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

/**
 * ACCOUNTS
 */
Breadcrumbs::register(
    'accounts.index', function (BreadCrumbGenerator $breadcrumbs, string $what) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.' . strtolower(e($what)) . '_accounts'), route('accounts.index', [$what]));
}
);

Breadcrumbs::register(
    'accounts.create', function (BreadCrumbGenerator $breadcrumbs, string $what) {
    $breadcrumbs->parent('accounts.index', $what);
    $breadcrumbs->push(trans('firefly.new_' . strtolower(e($what)) . '_account'), route('accounts.create', [$what]));
}
);

Breadcrumbs::register(
    'accounts.show', function (BreadCrumbGenerator $breadcrumbs, Account $account, string $moment, Carbon $start, Carbon $end) {
    $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

    $breadcrumbs->parent('accounts.index', $what);
    $breadcrumbs->push($account->name, route('accounts.show', [$account->id]));

    // push when is all:
    if ($moment === 'all') {
        $breadcrumbs->push(trans('firefly.everything'), route('accounts.show', [$account->id, 'all']));
    }
    // when is specific period or when empty:
    if ($moment !== 'all' && $moment !== '(nothing)') {
        $title = trans(
            'firefly.between_dates_breadcrumb', ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                                                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day')))]
        );
        $breadcrumbs->push($title, route('accounts.show', [$account->id, $moment, $start, $end]));
    }

}
);

Breadcrumbs::register(
    'accounts.delete', function (BreadCrumbGenerator $breadcrumbs, Account $account) {
    $breadcrumbs->parent('accounts.show', $account, '(nothing)', new Carbon, new Carbon);
    $breadcrumbs->push(trans('firefly.delete_account', ['name' => e($account->name)]), route('accounts.delete', [$account->id]));
}
);


Breadcrumbs::register(
    'accounts.edit', function (BreadCrumbGenerator $breadcrumbs, Account $account) {
    $breadcrumbs->parent('accounts.show', $account, '(nothing)', new Carbon, new Carbon);
    $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

    $breadcrumbs->push(trans('firefly.edit_' . $what . '_account', ['name' => e($account->name)]), route('accounts.edit', [$account->id]));
}
);

/**
 * ADMIN
 */
Breadcrumbs::register(
    'admin.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.administration'), route('admin.index'));
}
);

Breadcrumbs::register(
    'admin.users', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('admin.index');
    $breadcrumbs->push(trans('firefly.list_all_users'), route('admin.users'));
}
);

Breadcrumbs::register(
    'admin.users.show', function (BreadCrumbGenerator $breadcrumbs, User $user) {
    $breadcrumbs->parent('admin.users');
    $breadcrumbs->push(trans('firefly.single_user_administration', ['email' => $user->email]), route('admin.users.show', [$user->id]));
}
);
Breadcrumbs::register(
    'admin.users.edit', function (BreadCrumbGenerator $breadcrumbs, User $user) {
    $breadcrumbs->parent('admin.users');
    $breadcrumbs->push(trans('firefly.edit_user', ['email' => $user->email]), route('admin.users.edit', [$user->id]));
}
);

Breadcrumbs::register(
    'admin.users.domains', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('admin.index');
    $breadcrumbs->push(trans('firefly.blocked_domains'), route('admin.users.domains'));
}
);

Breadcrumbs::register(
    'admin.configuration.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('admin.index');
    $breadcrumbs->push(trans('firefly.instance_configuration'), route('admin.configuration.index'));
}
);


/**
 * ATTACHMENTS
 */
Breadcrumbs::register(
    'attachments.edit', function (BreadCrumbGenerator $breadcrumbs, Attachment $attachment) {
    $object = $attachment->attachable;
    if ($object instanceof TransactionJournal) {
        $breadcrumbs->parent('transactions.show', $object);
        $breadcrumbs->push($attachment->filename, route('attachments.edit', [$attachment]));

    } else {
        throw new FireflyException('Cannot make breadcrumb for attachment connected to object of type ' . get_class($object));
    }

}
);
Breadcrumbs::register(
    'attachments.delete', function (BreadCrumbGenerator $breadcrumbs, Attachment $attachment) {

    $object = $attachment->attachable;
    if ($object instanceof TransactionJournal) {
        $breadcrumbs->parent('transactions.show', $object);
        $breadcrumbs->push(trans('firefly.delete_attachment', ['name' => $attachment->filename]), route('attachments.edit', [$attachment]));

    } else {
        throw new FireflyException('Cannot make breadcrumb for attachment connected to object of type ' . get_class($object));
    }
}
);

/**
 * BILLS
 */
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


/**
 * BUDGETS
 */
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
    'budgets.no-budget', function (BreadCrumbGenerator $breadcrumbs, string $moment, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('budgets.index');
    $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));

    // push when is all:
    if ($moment === 'all') {
        $breadcrumbs->push(trans('firefly.everything'), route('budgets.no-budget', ['all']));
    }
    // when is specific period:
    if ($moment !== 'all') {
        $title = trans(
            'firefly.between_dates_breadcrumb', ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                                                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day')))]
        );
        $breadcrumbs->push($title, route('budgets.no-budget', [$moment]));
    }


}
);

Breadcrumbs::register(
    'budgets.show', function (BreadCrumbGenerator $breadcrumbs, Budget $budget) {
    $breadcrumbs->parent('budgets.index');
    $breadcrumbs->push(e($budget->name), route('budgets.show', [$budget->id]));
    $breadcrumbs->push(trans('firefly.everything'), route('budgets.show', [$budget->id]));
}
);

Breadcrumbs::register(
    'budgets.show.limit', function (BreadCrumbGenerator $breadcrumbs, Budget $budget, BudgetLimit $budgetLimit) {
    $breadcrumbs->parent('budgets.index');
    $breadcrumbs->push(e($budget->name), route('budgets.show', [$budget->id]));

    $title = trans(
        'firefly.between_dates_breadcrumb', ['start' => $budgetLimit->start_date->formatLocalized(strval(trans('config.month_and_day'))),
                                             'end'   => $budgetLimit->end_date->formatLocalized(strval(trans('config.month_and_day'))),]
    );

    $breadcrumbs->push(
        $title, route('budgets.show.limit', [$budget->id, $budgetLimit->id])
    );
}
);

/**
 * CATEGORIES
 */
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
    $breadcrumbs->parent('categories.show', $category, '', new Carbon, new Carbon);
    $breadcrumbs->push(trans('firefly.edit_category', ['name' => e($category->name)]), route('categories.edit', [$category->id]));
}
);
Breadcrumbs::register(
    'categories.delete', function (BreadCrumbGenerator $breadcrumbs, Category $category) {
    $breadcrumbs->parent('categories.show', $category, '', new Carbon, new Carbon);
    $breadcrumbs->push(trans('firefly.delete_category', ['name' => e($category->name)]), route('categories.delete', [$category->id]));
}
);

Breadcrumbs::register(
    'categories.show', function (BreadCrumbGenerator $breadcrumbs, Category $category, string $moment, Carbon $start, Carbon $end) {

    $breadcrumbs->parent('categories.index');
    $breadcrumbs->push($category->name, route('categories.show', [$category->id]));

    // push when is all:
    if ($moment === 'all') {
        $breadcrumbs->push(trans('firefly.everything'), route('categories.show', [$category->id, 'all']));
    }
    // when is specific period:
    if ($moment !== 'all') {
        $title = trans(
            'firefly.between_dates_breadcrumb', ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                                                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day')))]
        );
        $breadcrumbs->push($title, route('categories.show', [$category->id, $moment]));
    }
}
);


Breadcrumbs::register(
    'categories.no-category', function (BreadCrumbGenerator $breadcrumbs, string $moment, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('categories.index');
    $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));

    // push when is all:
    if ($moment === 'all') {
        $breadcrumbs->push(trans('firefly.everything'), route('categories.no-category', ['all']));
    }
    // when is specific period:
    if ($moment !== 'all') {
        $title = trans(
            'firefly.between_dates_breadcrumb', ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                                                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day')))]
        );
        $breadcrumbs->push($title, route('categories.no-category', [$moment]));
    }


}
);


/**
 * CURRENCIES
 */
Breadcrumbs::register(
    'currencies.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.currencies'), route('currencies.index'));
}
);

Breadcrumbs::register(
    'currencies.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('currencies.index');
    $breadcrumbs->push(trans('firefly.create_currency'), route('currencies.create'));
}
);

Breadcrumbs::register(
    'currencies.edit', function (BreadCrumbGenerator $breadcrumbs, TransactionCurrency $currency) {
    $breadcrumbs->parent('currencies.index');
    $breadcrumbs->push(trans('breadcrumbs.edit_currency', ['name' => e($currency->name)]), route('currencies.edit', [$currency->id]));
}
);
Breadcrumbs::register(
    'currencies.delete', function (BreadCrumbGenerator $breadcrumbs, TransactionCurrency $currency) {
    $breadcrumbs->parent('currencies.index');
    $breadcrumbs->push(trans('breadcrumbs.delete_currency', ['name' => e($currency->name)]), route('currencies.delete', [$currency->id]));
}
);

/**
 * EXPORT
 */
Breadcrumbs::register(
    'export.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.export_data'), route('export.index'));
}
);

/**
 * PIGGY BANKS
 */
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

Breadcrumbs::register(
    'piggy-banks.add-money-mobile', function (BreadCrumbGenerator $breadcrumbs, PiggyBank $piggyBank) {
    $breadcrumbs->parent('piggy-banks.show', $piggyBank);
    $breadcrumbs->push(trans('firefly.add_money_to_piggy', ['name' => $piggyBank->name]), route('piggy-banks.add-money-mobile', [$piggyBank->id]));
}
);

Breadcrumbs::register(
    'piggy-banks.remove-money-mobile', function (BreadCrumbGenerator $breadcrumbs, PiggyBank $piggyBank) {
    $breadcrumbs->parent('piggy-banks.show', $piggyBank);
    $breadcrumbs->push(
        trans('firefly.remove_money_from_piggy_title', ['name' => $piggyBank->name]), route('piggy-banks.remove-money-mobile', [$piggyBank->id])
    );
}
);

/**
 * IMPORT
 */
Breadcrumbs::register(
    'import.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.import'), route('import.index'));
}
);

Breadcrumbs::register(
    'import.configure', function (BreadCrumbGenerator $breadcrumbs, ImportJob $job) {
    $breadcrumbs->parent('import.index');
    $breadcrumbs->push(trans('firefly.import_config_sub_title', ['key' => $job->key]), route('import.configure', [$job->key]));
}
);
Breadcrumbs::register(
    'import.status', function (BreadCrumbGenerator $breadcrumbs, ImportJob $job) {
    $breadcrumbs->parent('import.index');
    $breadcrumbs->push(trans('firefly.import_status_bread_crumb', ['key' => $job->key]), route('import.status', [$job->key]));
}
);

/**
 * PREFERENCES
 */
Breadcrumbs::register(
    'preferences.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));
}
);

Breadcrumbs::register(
    'preferences.code', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));

}
);

/**
 * PROFILE
 */
Breadcrumbs::register(
    'profile.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));

}
);
Breadcrumbs::register(
    'profile.change-password', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('profile.index');
    $breadcrumbs->push(trans('breadcrumbs.changePassword'), route('profile.change-password'));

}
);
Breadcrumbs::register(
    'profile.delete-account', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('profile.index');
    $breadcrumbs->push(trans('firefly.delete_account'), route('profile.delete-account'));

}
);

/**
 * REPORTS
 */
Breadcrumbs::register(
    'reports.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.reports'), route('reports.index'));
}
);

Breadcrumbs::register(
    'reports.report.audit', function (BreadCrumbGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $startString = $start->formatLocalized($monthFormat);
    $endString   = $end->formatLocalized($monthFormat);
    $title       = (string)trans('firefly.report_audit', ['start' => $startString, 'end' => $endString]);

    $breadcrumbs->push($title, route('reports.report.audit', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
}
);
Breadcrumbs::register(
    'reports.report.budget', function (BreadCrumbGenerator $breadcrumbs, string $accountIds, string $budgetIds, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $startString = $start->formatLocalized($monthFormat);
    $endString   = $end->formatLocalized($monthFormat);
    $title       = (string)trans('firefly.report_budget', ['start' => $startString, 'end' => $endString]);

    $breadcrumbs->push($title, route('reports.report.budget', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]));
}
);

Breadcrumbs::register(
    'reports.report.tag', function (BreadCrumbGenerator $breadcrumbs, string $accountIds, string $tagTags, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $startString = $start->formatLocalized($monthFormat);
    $endString   = $end->formatLocalized($monthFormat);
    $title       = (string)trans('firefly.report_tag', ['start' => $startString, 'end' => $endString]);

    $breadcrumbs->push($title, route('reports.report.tag', [$accountIds, $tagTags, $start->format('Ymd'), $end->format('Ymd')]));
}
);

Breadcrumbs::register(
    'reports.report.category', function (BreadCrumbGenerator $breadcrumbs, string $accountIds, string $categoryIds, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $startString = $start->formatLocalized($monthFormat);
    $endString   = $end->formatLocalized($monthFormat);
    $title       = (string)trans('firefly.report_category', ['start' => $startString, 'end' => $endString]);

    $breadcrumbs->push($title, route('reports.report.category', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]));
}
);

Breadcrumbs::register(
    'reports.report.default', function (BreadCrumbGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $startString = $start->formatLocalized($monthFormat);
    $endString   = $end->formatLocalized($monthFormat);
    $title       = (string)trans('firefly.report_default', ['start' => $startString, 'end' => $endString]);

    $breadcrumbs->push($title, route('reports.report.default', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
}
);

/**
 * New user Controller
 */
Breadcrumbs::register(
    'new-user.index', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('firefly.getting_started'), route('new-user.index'));
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

Breadcrumbs::register(
    'rules.create', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.make_new_rule', ['title' => $ruleGroup->title]), route('rules.create', [$ruleGroup]));
}
);
Breadcrumbs::register(
    'rules.edit', function (BreadCrumbGenerator $breadcrumbs, Rule $rule) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.edit_rule', ['title' => $rule->title]), route('rules.edit', [$rule]));
}
);
Breadcrumbs::register(
    'rules.delete', function (BreadCrumbGenerator $breadcrumbs, Rule $rule) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.delete_rule', ['title' => $rule->title]), route('rules.delete', [$rule]));
}
);
Breadcrumbs::register(
    'rule-groups.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.make_new_rule_group'), route('rule-groups.create'));
}
);
Breadcrumbs::register(
    'rule-groups.edit', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.edit', [$ruleGroup]));
}
);
Breadcrumbs::register(
    'rule-groups.delete', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.delete', [$ruleGroup]));
}
);

Breadcrumbs::register(
    'rule-groups.select-transactions', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.rule_group_select_transactions', ['title' => $ruleGroup->title]), route('rule-groups.select-transactions', [$ruleGroup]));
}
);

Breadcrumbs::register(
    'rule-groups.select_transactions', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(
        trans('firefly.execute_group_on_existing_transactions', ['title' => $ruleGroup->title]), route('rule-groups.select_transactions', [$ruleGroup])
    );
}
);


/**
 * SEARCH
 */
Breadcrumbs::register(
    'search.index', function (BreadCrumbGenerator $breadcrumbs, $query) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.searchResult', ['query' => e($query)]), route('search.index'));
}
);


/**
 * TAGS
 */
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
    $breadcrumbs->parent('tags.show', $tag, '', new Carbon, new Carbon);
    $breadcrumbs->push(trans('breadcrumbs.edit_tag', ['tag' => e($tag->tag)]), route('tags.edit', [$tag->id]));
}
);

Breadcrumbs::register(
    'tags.delete', function (BreadCrumbGenerator $breadcrumbs, Tag $tag) {
    $breadcrumbs->parent('tags.show', $tag, '', new Carbon, new Carbon);
    $breadcrumbs->push(trans('breadcrumbs.delete_tag', ['tag' => e($tag->tag)]), route('tags.delete', [$tag->id]));
}
);


Breadcrumbs::register(
    'tags.show', function (BreadCrumbGenerator $breadcrumbs, Tag $tag, string $moment, Carbon $start, Carbon $end) {
    $breadcrumbs->parent('tags.index');
    $breadcrumbs->push(e($tag->tag), route('tags.show', [$tag->id, $moment]));
    if ($moment === 'all') {
        $breadcrumbs->push(trans('firefly.everything'), route('tags.show', [$tag->id, $moment]));
    }
    if ($moment !== 'all') {
        $title = trans(
            'firefly.between_dates_breadcrumb', ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                                                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day')))]
        );
        $breadcrumbs->push($title, route('tags.show', [$tag->id, $moment]));
    }
}
);

/**
 * TRANSACTIONS
 */
Breadcrumbs::register(
    'transactions.index', function (BreadCrumbGenerator $breadcrumbs, string $what, string $moment = '', Carbon $start, Carbon $end) {


    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));
    if ($moment === 'all') {
        $breadcrumbs->push(trans('firefly.everything'), route('transactions.index', [$what, 'all']));
    }

    // when is specific period:
    if ($moment !== 'all') {
        $title = trans(
            'firefly.between_dates_breadcrumb', ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                                                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day')))]
        );
        $breadcrumbs->push($title, route('transactions.index', [$what, $moment]));
    }

}
);

Breadcrumbs::register(
    'transactions.create', function (BreadCrumbGenerator $breadcrumbs, string $what) {
    $breadcrumbs->parent('transactions.index', $what, '', new Carbon, new Carbon);
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

    $what = strtolower($journal->transactionType->type);
    $breadcrumbs->parent('transactions.index', $what, '', new Carbon, new Carbon);
    $breadcrumbs->push($journal->description, route('transactions.show', [$journal->id]));
}
);

Breadcrumbs::register(
    'transactions.convert', function (BreadCrumbGenerator $breadcrumbs, TransactionType $destinationType, TransactionJournal $journal) {

    $breadcrumbs->parent('transactions.show', $journal);
    $breadcrumbs->push(
        trans('firefly.convert_to_' . $destinationType->type, ['description' => $journal->description]),
        route('transactions.convert.index', [strtolower($destinationType->type), $journal->id])
    );
}
);

/**
 * MASS TRANSACTION EDIT / DELETE
 */
Breadcrumbs::register(
    'transactions.mass.edit', function (BreadCrumbGenerator $breadcrumbs, Collection $journals) {

    if ($journals->count() > 0) {
        $journalIds = $journals->pluck('id')->toArray();
        $what       = strtolower($journals->first()->transactionType->type);
        $breadcrumbs->parent('transactions.index', $what, '', new Carbon, new Carbon);
        $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.edit', $journalIds));

        return;
    }

    $breadcrumbs->parent('index');
}
);

Breadcrumbs::register(
    'transactions.mass.delete', function (BreadCrumbGenerator $breadcrumbs, Collection $journals) {

    $journalIds = $journals->pluck('id')->toArray();
    $what       = strtolower($journals->first()->transactionType->type);
    $breadcrumbs->parent('transactions.index', $what, '', new Carbon, new Carbon);
    $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.delete', $journalIds));
}
);


/**
 * SPLIT
 */
Breadcrumbs::register(
    'transactions.split.edit', function (BreadCrumbGenerator $breadcrumbs, TransactionJournal $journal) {
    $breadcrumbs->parent('transactions.show', $journal);
    $breadcrumbs->push(trans('breadcrumbs.edit_journal', ['description' => $journal->description]), route('transactions.split.edit', [$journal->id]));
}
);
