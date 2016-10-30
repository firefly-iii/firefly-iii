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

declare(strict_types = 1);
use Carbon\Carbon;
use DaveJamesMiller\Breadcrumbs\Generator as BreadCrumbGenerator;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;

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
    'accounts.show', function (BreadCrumbGenerator $breadcrumbs, Account $account) {
    $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);
    $breadcrumbs->parent('accounts.index', $what);
    $breadcrumbs->push(e($account->name), route('accounts.show', [$account->id]));
}
);

Breadcrumbs::register(
    'accounts.show.date', function (BreadCrumbGenerator $breadcrumbs, Account $account, Carbon $date) {
    $breadcrumbs->parent('accounts.show', $account);

    $range = Preferences::get('viewRange', '1M')->data;
    $title = $account->name . ' (' . Navigation::periodShow($date, $range) . ')';

    $breadcrumbs->push($title, route('accounts.show.date', [$account->id, $date->format('Y-m-d')]));
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

/**
 * CURRENCIES
 */
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

/**
 * PREFERENCES
 */
Breadcrumbs::register(
    'preferences', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences'));

}
);

Breadcrumbs::register(
    'preferences.code', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences'));

}
);

/**
 * PROFILE
 */
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
    'reports.report', function (BreadCrumbGenerator $breadcrumbs, Carbon $start, Carbon $end, $reportType, $accountIds) {
    $breadcrumbs->parent('reports.index');

    $monthFormat = (string)trans('config.month_and_day');
    $title       = (string)trans(
        'firefly.report_' . $reportType,
        ['start' => $start->formatLocalized($monthFormat), 'end' => $end->formatLocalized($monthFormat)]
    );

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

Breadcrumbs::register(
    'rules.rule.create', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.make_new_rule', ['title' => $ruleGroup->title]), route('rules.rule.create', [$ruleGroup]));
}
);
Breadcrumbs::register(
    'rules.rule.edit', function (BreadCrumbGenerator $breadcrumbs, Rule $rule) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.edit_rule', ['title' => $rule->title]), route('rules.rule.edit', [$rule]));
}
);
Breadcrumbs::register(
    'rules.rule.delete', function (BreadCrumbGenerator $breadcrumbs, Rule $rule) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.delete_rule', ['title' => $rule->title]), route('rules.rule.delete', [$rule]));
}
);
Breadcrumbs::register(
    'rules.rule-group.create', function (BreadCrumbGenerator $breadcrumbs) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.make_new_rule_group'), route('rules.rule-group.create'));
}
);
Breadcrumbs::register(
    'rules.rule-group.edit', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]), route('rules.rule-group.edit', [$ruleGroup]));
}
);
Breadcrumbs::register(
    'rules.rule-group.delete', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]), route('rules.rule-group.delete', [$ruleGroup]));
}
);

Breadcrumbs::register(
    'rules.rule-group.select_transactions', function (BreadCrumbGenerator $breadcrumbs, RuleGroup $ruleGroup) {
    $breadcrumbs->parent('rules.index');
    $breadcrumbs->push(
        trans('firefly.execute_group_on_existing_transactions', ['title' => $ruleGroup->title]), route('rules.rule-group.select_transactions', [$ruleGroup])
    );
}
);


/**
 * SEARCH
 */
Breadcrumbs::register(
    'search', function (BreadCrumbGenerator $breadcrumbs, $query) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.searchResult', ['query' => e($query)]), route('search'));
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

/**
 * TRANSACTIONS
 */
Breadcrumbs::register(
    'transactions.index', function (BreadCrumbGenerator $breadcrumbs, string $what) {
    $breadcrumbs->parent('home');
    $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));
}
);
Breadcrumbs::register(
    'transactions.create', function (BreadCrumbGenerator $breadcrumbs, string $what) {
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

    $what = strtolower($journal->transactionType->type);
    $breadcrumbs->parent('transactions.index', $what);
    $breadcrumbs->push($journal->description, route('transactions.show', [$journal->id]));
}
);

Breadcrumbs::register(
    'transactions.convert', function (BreadCrumbGenerator $breadcrumbs, TransactionType $destinationType, TransactionJournal $journal) {

    $breadcrumbs->parent('transactions.show', $journal);
    $breadcrumbs->push(
        trans('firefly.convert_to_' . $destinationType->type, ['description' => $journal->description]),
        route('transactions.convert', [strtolower($destinationType->type), $journal->id])
    );
}
);


/**
 * SPLIT
 */
Breadcrumbs::register(
    'transactions.edit-split', function (BreadCrumbGenerator $breadcrumbs, TransactionJournal $journal) {
    $breadcrumbs->parent('transactions.show', $journal);
    $breadcrumbs->push(trans('breadcrumbs.edit_journal', ['description' => $journal->description]), route('transactions.edit-split', [$journal->id]));
}
);

Breadcrumbs::register(
    'split.journal.create', function (BreadCrumbGenerator $breadcrumbs, string $what) {
    $breadcrumbs->parent('transactions.index', $what);
    $breadcrumbs->push(trans('breadcrumbs.create_' . e($what)), route('split.journal.create', [$what]));
}
);
