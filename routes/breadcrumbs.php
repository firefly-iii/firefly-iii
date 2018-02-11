<?php
/**
 * breadcrumbs.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

use Carbon\Carbon;
use DaveJamesMiller\Breadcrumbs\BreadcrumbsGenerator;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;

// HOME
Breadcrumbs::register(
    'home',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
    }
);

Breadcrumbs::register(
    'index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
    }
);

// ACCOUNTS
Breadcrumbs::register(
    'accounts.index',
    function (BreadCrumbsGenerator $breadcrumbs, string $what) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.' . strtolower(e($what)) . '_accounts'), route('accounts.index', [$what]));
    }
);

Breadcrumbs::register(
    'accounts.create',
    function (BreadCrumbsGenerator $breadcrumbs, string $what) {
        $breadcrumbs->parent('accounts.index', $what);
        $breadcrumbs->push(trans('firefly.new_' . strtolower(e($what)) . '_account'), route('accounts.create', [$what]));
    }
);

Breadcrumbs::register(
    'accounts.show',
    function (BreadCrumbsGenerator $breadcrumbs, Account $account, Carbon $start = null, Carbon $end = null) {
        $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

        $breadcrumbs->parent('accounts.index', $what);
        $breadcrumbs->push($account->name, route('accounts.show', [$account->id]));
        if (!is_null($start) && !is_null($end)) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $start ? $start->formatLocalized(strval(trans('config.month_and_day'))) : '',
                 'end'   => $end ? $end->formatLocalized(strval(trans('config.month_and_day'))) : '',]
            );
            $breadcrumbs->push($title, route('accounts.show', $account));
        }
    }
);

Breadcrumbs::register(
    'accounts.reconcile',
    function (BreadCrumbsGenerator $breadcrumbs, Account $account) {
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push(trans('firefly.reconcile_account', ['account' => $account->name]), route('accounts.reconcile', [$account->id]));
    }
);

Breadcrumbs::register(
    'accounts.reconcile.show',
    function (BreadCrumbsGenerator $breadcrumbs, Account $account, TransactionJournal $journal) {
        $breadcrumbs->parent('accounts.show', $account);
        $title = trans('firefly.reconciliation') . ' "' . $journal->description . '"';
        $breadcrumbs->push($title, route('accounts.reconcile.show', [$journal->id]));
    }
);

Breadcrumbs::register(
    'accounts.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Account $account) {
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push(trans('firefly.delete_account', ['name' => $account->name]), route('accounts.delete', [$account->id]));
    }
);

Breadcrumbs::register(
    'accounts.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Account $account) {
        $breadcrumbs->parent('accounts.show', $account);
        $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

        $breadcrumbs->push(trans('firefly.edit_' . $what . '_account', ['name' => $account->name]), route('accounts.edit', [$account->id]));
    }
);

// ADMIN
Breadcrumbs::register(
    'admin.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.administration'), route('admin.index'));
    }
);

Breadcrumbs::register(
    'admin.users',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('admin.index');
        $breadcrumbs->push(trans('firefly.list_all_users'), route('admin.users'));
    }
);

Breadcrumbs::register(
    'admin.users.show',
    function (BreadCrumbsGenerator $breadcrumbs, User $user) {
        $breadcrumbs->parent('admin.users');
        $breadcrumbs->push(trans('firefly.single_user_administration', ['email' => $user->email]), route('admin.users.show', [$user->id]));
    }
);
Breadcrumbs::register(
    'admin.users.edit',
    function (BreadCrumbsGenerator $breadcrumbs, User $user) {
        $breadcrumbs->parent('admin.users');
        $breadcrumbs->push(trans('firefly.edit_user', ['email' => $user->email]), route('admin.users.edit', [$user->id]));
    }
);
Breadcrumbs::register(
    'admin.users.delete',
    function (BreadCrumbsGenerator $breadcrumbs, User $user) {
        $breadcrumbs->parent('admin.users');
        $breadcrumbs->push(trans('firefly.delete_user', ['email' => $user->email]), route('admin.users.delete', [$user->id]));
    }
);

Breadcrumbs::register(
    'admin.users.domains',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('admin.index');
        $breadcrumbs->push(trans('firefly.blocked_domains'), route('admin.users.domains'));
    }
);

Breadcrumbs::register(
    'admin.configuration.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('admin.index');
        $breadcrumbs->push(trans('firefly.instance_configuration'), route('admin.configuration.index'));
    }
);
Breadcrumbs::register(
    'admin.update-check',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('admin.index');
        $breadcrumbs->push(trans('firefly.update_check_title'), route('admin.update-check'));
    }
);

Breadcrumbs::register(
    'admin.links.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('admin.index');
        $breadcrumbs->push(trans('firefly.journal_link_configuration'), route('admin.links.index'));
    }
);

Breadcrumbs::register(
    'admin.links.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('admin.links.index');
        $breadcrumbs->push(trans('firefly.create_new_link_type'), route('admin.links.create'));
    }
);

Breadcrumbs::register(
    'admin.links.show',
    function (BreadCrumbsGenerator $breadcrumbs, LinkType $linkType) {
        $breadcrumbs->parent('admin.links.index');
        $breadcrumbs->push(trans('firefly.overview_for_link', ['name' => $linkType->name]), route('admin.links.show', [$linkType->id]));
    }
);

Breadcrumbs::register(
    'admin.links.edit',
    function (BreadCrumbsGenerator $breadcrumbs, LinkType $linkType) {
        $breadcrumbs->parent('admin.links.index');
        $breadcrumbs->push(trans('firefly.edit_link_type', ['name' => $linkType->name]), route('admin.links.edit', [$linkType->id]));
    }
);

Breadcrumbs::register(
    'admin.links.delete',
    function (BreadCrumbsGenerator $breadcrumbs, LinkType $linkType) {
        $breadcrumbs->parent('admin.links.index');
        $breadcrumbs->push(trans('firefly.delete_link_type', ['name' => $linkType->name]), route('admin.links.delete', [$linkType->id]));
    }
);

Breadcrumbs::register(
    'transactions.link.delete',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionJournalLink $link) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.delete_journal_link'), route('transactions.link.delete', $link->id));
    }
);

// ATTACHMENTS
Breadcrumbs::register(
    'attachments.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Attachment $attachment) {
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
    'attachments.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Attachment $attachment) {
        $object = $attachment->attachable;
        if ($object instanceof TransactionJournal) {
            $breadcrumbs->parent('transactions.show', $object);
            $breadcrumbs->push(trans('firefly.delete_attachment', ['name' => $attachment->filename]), route('attachments.edit', [$attachment]));
        } else {
            throw new FireflyException('Cannot make breadcrumb for attachment connected to object of type ' . get_class($object));
        }
    }
);

// BILLS
Breadcrumbs::register(
    'bills.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.bills'), route('bills.index'));
    }
);
Breadcrumbs::register(
    'bills.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('bills.index');
        $breadcrumbs->push(trans('breadcrumbs.newBill'), route('bills.create'));
    }
);

Breadcrumbs::register(
    'bills.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Bill $bill) {
        $breadcrumbs->parent('bills.show', $bill);
        $breadcrumbs->push(trans('breadcrumbs.edit_bill', ['name' => $bill->name]), route('bills.edit', [$bill->id]));
    }
);
Breadcrumbs::register(
    'bills.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Bill $bill) {
        $breadcrumbs->parent('bills.show', $bill);
        $breadcrumbs->push(trans('breadcrumbs.delete_bill', ['name' => $bill->name]), route('bills.delete', [$bill->id]));
    }
);

Breadcrumbs::register(
    'bills.show',
    function (BreadCrumbsGenerator $breadcrumbs, Bill $bill) {
        $breadcrumbs->parent('bills.index');
        $breadcrumbs->push($bill->name, route('bills.show', [$bill->id]));
    }
);

// BUDGETS
Breadcrumbs::register(
    'budgets.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.budgets'), route('budgets.index'));
    }
);
Breadcrumbs::register(
    'budgets.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(trans('firefly.create_new_budget'), route('budgets.create'));
    }
);

Breadcrumbs::register(
    'budgets.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Budget $budget) {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push(trans('firefly.edit_budget', ['name' => $budget->name]), route('budgets.edit', [$budget->id]));
    }
);
Breadcrumbs::register(
    'budgets.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Budget $budget) {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push(trans('firefly.delete_budget', ['name' => $budget->name]), route('budgets.delete', [$budget->id]));
    }
);

Breadcrumbs::register(
    'budgets.no-budget',
    function (BreadCrumbsGenerator $breadcrumbs, string $moment, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));

        // push when is all:
        if ('all' === $moment) {
            $breadcrumbs->push(trans('firefly.everything'), route('budgets.no-budget', ['all']));
        }
        // when is specific period or when empty:
        if ('all' !== $moment && '(nothing)' !== $moment) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day'))),]
            );
            $breadcrumbs->push($title, route('budgets.no-budget', [$moment]));
        }
    }
);

Breadcrumbs::register(
    'budgets.show',
    function (BreadCrumbsGenerator $breadcrumbs, Budget $budget) {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push($budget->name, route('budgets.show', [$budget->id]));
        $breadcrumbs->push(trans('firefly.everything'), route('budgets.show', [$budget->id]));
    }
);

Breadcrumbs::register(
    'budgets.show.limit',
    function (BreadCrumbsGenerator $breadcrumbs, Budget $budget, BudgetLimit $budgetLimit) {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push($budget->name, route('budgets.show', [$budget->id]));

        $title = trans(
            'firefly.between_dates_breadcrumb',
            ['start' => $budgetLimit->start_date->formatLocalized(strval(trans('config.month_and_day'))),
             'end'   => $budgetLimit->end_date->formatLocalized(strval(trans('config.month_and_day'))),]
        );

        $breadcrumbs->push(
            $title,
            route('budgets.show.limit', [$budget->id, $budgetLimit->id])
        );
    }
);

// CATEGORIES
Breadcrumbs::register(
    'categories.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.categories'), route('categories.index'));
    }
);
Breadcrumbs::register(
    'categories.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(trans('firefly.new_category'), route('categories.create'));
    }
);

Breadcrumbs::register(
    'categories.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Category $category) {
        $breadcrumbs->parent('categories.show', $category, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push(trans('firefly.edit_category', ['name' => $category->name]), route('categories.edit', [$category->id]));
    }
);
Breadcrumbs::register(
    'categories.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Category $category) {
        $breadcrumbs->parent('categories.show', $category, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push(trans('firefly.delete_category', ['name' => $category->name]), route('categories.delete', [$category->id]));
    }
);

Breadcrumbs::register(
    'categories.show',
    function (BreadCrumbsGenerator $breadcrumbs, Category $category, string $moment, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push($category->name, route('categories.show', [$category->id]));

        // push when is all:
        if ('all' === $moment) {
            $breadcrumbs->push(trans('firefly.everything'), route('categories.show', [$category->id, 'all']));
        }
        // when is specific period or when empty:
        if ('all' !== $moment && '(nothing)' !== $moment) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day'))),]
            );
            $breadcrumbs->push($title, route('categories.show', [$category->id, $moment]));
        }
    }
);

Breadcrumbs::register(
    'categories.no-category',
    function (BreadCrumbsGenerator $breadcrumbs, string $moment, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));

        // push when is all:
        if ('all' === $moment) {
            $breadcrumbs->push(trans('firefly.everything'), route('categories.no-category', ['all']));
        }
        // when is specific period or when empty:
        if ('all' !== $moment && '(nothing)' !== $moment) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day'))),]
            );
            $breadcrumbs->push($title, route('categories.no-category', [$moment]));
        }
    }
);

// CURRENCIES
Breadcrumbs::register(
    'currencies.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.currencies'), route('currencies.index'));
    }
);

Breadcrumbs::register(
    'currencies.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('currencies.index');
        $breadcrumbs->push(trans('firefly.create_currency'), route('currencies.create'));
    }
);

Breadcrumbs::register(
    'currencies.edit',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionCurrency $currency) {
        $breadcrumbs->parent('currencies.index');
        $breadcrumbs->push(trans('breadcrumbs.edit_currency', ['name' => $currency->name]), route('currencies.edit', [$currency->id]));
    }
);
Breadcrumbs::register(
    'currencies.delete',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionCurrency $currency) {
        $breadcrumbs->parent('currencies.index');
        $breadcrumbs->push(trans('breadcrumbs.delete_currency', ['name' => $currency->name]), route('currencies.delete', [$currency->id]));
    }
);

// EXPORT
Breadcrumbs::register(
    'export.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.export_data'), route('export.index'));
    }
);

// PIGGY BANKS
Breadcrumbs::register(
    'piggy-banks.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.piggyBanks'), route('piggy-banks.index'));
    }
);
Breadcrumbs::register(
    'piggy-banks.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('piggy-banks.index');
        $breadcrumbs->push(trans('breadcrumbs.newPiggyBank'), route('piggy-banks.create'));
    }
);

Breadcrumbs::register(
    'piggy-banks.edit',
    function (BreadCrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(trans('breadcrumbs.edit_piggyBank', ['name' => $piggyBank->name]), route('piggy-banks.edit', [$piggyBank->id]));
    }
);
Breadcrumbs::register(
    'piggy-banks.delete',
    function (BreadCrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]), route('piggy-banks.delete', [$piggyBank->id]));
    }
);

Breadcrumbs::register(
    'piggy-banks.show',
    function (BreadCrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
        $breadcrumbs->parent('piggy-banks.index');
        $breadcrumbs->push($piggyBank->name, route('piggy-banks.show', [$piggyBank->id]));
    }
);

Breadcrumbs::register(
    'piggy-banks.add-money-mobile',
    function (BreadCrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(trans('firefly.add_money_to_piggy', ['name' => $piggyBank->name]), route('piggy-banks.add-money-mobile', [$piggyBank->id]));
    }
);

Breadcrumbs::register(
    'piggy-banks.remove-money-mobile',
    function (BreadCrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(
            trans('firefly.remove_money_from_piggy_title', ['name' => $piggyBank->name]),
            route('piggy-banks.remove-money-mobile', [$piggyBank->id])
        );
    }
);

// IMPORT
Breadcrumbs::register(
    'import.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.import'), route('import.index'));
    }
);

Breadcrumbs::register(
    'import.configure',
    function (BreadCrumbsGenerator $breadcrumbs, ImportJob $job) {
        $breadcrumbs->parent('import.index');
        $breadcrumbs->push(trans('import.config_sub_title', ['key' => $job->key]), route('import.configure', [$job->key]));
    }
);

Breadcrumbs::register(
    'import.prerequisites',
    function (BreadCrumbsGenerator $breadcrumbs, string $bank) {
        $breadcrumbs->parent('import.index');
        $breadcrumbs->push(trans('import.prerequisites'), route('import.prerequisites', [$bank]));
    }
);


Breadcrumbs::register(
    'import.status',
    function (BreadCrumbsGenerator $breadcrumbs, ImportJob $job) {
        $breadcrumbs->parent('import.index');
        $breadcrumbs->push(trans('import.status_bread_crumb', ['key' => $job->key]), route('import.status', [$job->key]));
    }
);


// PREFERENCES
Breadcrumbs::register(
    'preferences.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));
    }
);

Breadcrumbs::register(
    'preferences.code',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));
    }
);

// PROFILE
Breadcrumbs::register(
    'profile.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
    }
);
Breadcrumbs::register(
    'profile.change-password',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('breadcrumbs.changePassword'), route('profile.change-password'));
    }
);

Breadcrumbs::register(
    'profile.change-email',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('breadcrumbs.change_email'), route('profile.change-email'));
    }
);

Breadcrumbs::register(
    'profile.delete-account',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('firefly.delete_account'), route('profile.delete-account'));
    }
);

// REPORTS
Breadcrumbs::register(
    'reports.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.reports'), route('reports.index'));
    }
);

Breadcrumbs::register(
    'reports.report.audit',
    function (BreadCrumbsGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day');
        $startString = $start->formatLocalized($monthFormat);
        $endString   = $end->formatLocalized($monthFormat);
        $title       = (string)trans('firefly.report_audit', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.audit', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);
Breadcrumbs::register(
    'reports.report.budget',
    function (BreadCrumbsGenerator $breadcrumbs, string $accountIds, string $budgetIds, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day');
        $startString = $start->formatLocalized($monthFormat);
        $endString   = $end->formatLocalized($monthFormat);
        $title       = (string)trans('firefly.report_budget', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.budget', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::register(
    'reports.report.tag',
    function (BreadCrumbsGenerator $breadcrumbs, string $accountIds, string $tagTags, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day');
        $startString = $start->formatLocalized($monthFormat);
        $endString   = $end->formatLocalized($monthFormat);
        $title       = (string)trans('firefly.report_tag', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.tag', [$accountIds, $tagTags, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::register(
    'reports.report.category',
    function (BreadCrumbsGenerator $breadcrumbs, string $accountIds, string $categoryIds, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day');
        $startString = $start->formatLocalized($monthFormat);
        $endString   = $end->formatLocalized($monthFormat);
        $title       = (string)trans('firefly.report_category', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.category', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::register(
    'reports.report.account',
    function (BreadCrumbsGenerator $breadcrumbs, string $accountIds, string $expenseIds, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day');
        $startString = $start->formatLocalized($monthFormat);
        $endString   = $end->formatLocalized($monthFormat);
        $title       = (string)trans('firefly.report_account', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.account', [$accountIds, $expenseIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::register(
    'reports.report.default',
    function (BreadCrumbsGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day');
        $startString = $start->formatLocalized($monthFormat);
        $endString   = $end->formatLocalized($monthFormat);
        $title       = (string)trans('firefly.report_default', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.default', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

// New user Controller
Breadcrumbs::register(
    'new-user.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.getting_started'), route('new-user.index'));
    }
);

// Rules
Breadcrumbs::register(
    'rules.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.rules'), route('rules.index'));
    }
);

Breadcrumbs::register(
    'rules.create',
    function (BreadCrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.make_new_rule', ['title' => $ruleGroup->title]), route('rules.create', [$ruleGroup]));
    }
);
Breadcrumbs::register(
    'rules.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Rule $rule) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.edit_rule', ['title' => $rule->title]), route('rules.edit', [$rule]));
    }
);
Breadcrumbs::register(
    'rules.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Rule $rule) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.delete_rule', ['title' => $rule->title]), route('rules.delete', [$rule]));
    }
);
Breadcrumbs::register(
    'rule-groups.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.make_new_rule_group'), route('rule-groups.create'));
    }
);
Breadcrumbs::register(
    'rule-groups.edit',
    function (BreadCrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.edit', [$ruleGroup]));
    }
);
Breadcrumbs::register(
    'rule-groups.delete',
    function (BreadCrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.delete', [$ruleGroup]));
    }
);

Breadcrumbs::register(
    'rules.select-transactions',
    function (BreadCrumbsGenerator $breadcrumbs, Rule $rule) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(
            trans('firefly.rule_select_transactions', ['title' => $rule->title]), route('rules.select-transactions', [$rule])
        );
    }
);

Breadcrumbs::register(
    'rule-groups.select-transactions',
    function (BreadCrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(
            trans('firefly.rule_group_select_transactions', ['title' => $ruleGroup->title]), route('rule-groups.select-transactions', [$ruleGroup])
        );
    }
);

// SEARCH
Breadcrumbs::register(
    'search.index',
    function (BreadCrumbsGenerator $breadcrumbs, $query) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.search_result', ['query' => $query]), route('search.index'));
    }
);

// TAGS
Breadcrumbs::register(
    'tags.index',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.tags'), route('tags.index'));
    }
);

Breadcrumbs::register(
    'tags.create',
    function (BreadCrumbsGenerator $breadcrumbs) {
        $breadcrumbs->parent('tags.index');
        $breadcrumbs->push(trans('breadcrumbs.createTag'), route('tags.create'));
    }
);

Breadcrumbs::register(
    'tags.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Tag $tag) {
        $breadcrumbs->parent('tags.show', $tag, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push(trans('breadcrumbs.edit_tag', ['tag' => $tag->tag]), route('tags.edit', [$tag->id]));
    }
);

Breadcrumbs::register(
    'tags.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Tag $tag) {
        $breadcrumbs->parent('tags.show', $tag, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push(trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]), route('tags.delete', [$tag->id]));
    }
);

Breadcrumbs::register(
    'tags.show',
    function (BreadCrumbsGenerator $breadcrumbs, Tag $tag, string $moment, Carbon $start, Carbon $end) {
        $breadcrumbs->parent('tags.index');
        $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id, $moment]));
        if ('all' === $moment) {
            $breadcrumbs->push(trans('firefly.everything'), route('tags.show', [$tag->id, $moment]));
        }
        // when is specific period or when empty:
        if ('all' !== $moment && '(nothing)' !== $moment) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day'))),]
            );
            $breadcrumbs->push($title, route('tags.show', [$tag->id, $moment]));
        }
    }
);

// TRANSACTIONS
Breadcrumbs::register(
    'transactions.index',
    function (BreadCrumbsGenerator $breadcrumbs, string $what, string $moment = '', Carbon $start, Carbon $end) {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));
        if ('all' === $moment) {
            $breadcrumbs->push(trans('firefly.everything'), route('transactions.index', [$what, 'all']));
        }

        // when is specific period or when empty:
        if ('all' !== $moment && '(nothing)' !== $moment) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $start->formatLocalized(strval(trans('config.month_and_day'))),
                 'end'   => $end->formatLocalized(strval(trans('config.month_and_day'))),]
            );
            $breadcrumbs->push($title, route('transactions.index', [$what, $moment]));
        }
    }
);

Breadcrumbs::register(
    'transactions.create',
    function (BreadCrumbsGenerator $breadcrumbs, string $what) {
        $breadcrumbs->parent('transactions.index', $what, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push(trans('breadcrumbs.create_' . e($what)), route('transactions.create', [$what]));
    }
);

Breadcrumbs::register(
    'transactions.edit',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push(trans('breadcrumbs.edit_journal', ['description' => $journal->description]), route('transactions.edit', [$journal->id]));
    }
);

// also edit reconciliations:
Breadcrumbs::register(
    'accounts.reconcile.edit',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push(
            trans('breadcrumbs.edit_reconciliation', ['description' => $journal->description]), route('accounts.reconcile.edit', [$journal->id])
        );
    }
);

Breadcrumbs::register(
    'transactions.delete',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push(trans('breadcrumbs.delete_journal', ['description' => $journal->description]), route('transactions.delete', [$journal->id]));
    }
);

Breadcrumbs::register(
    'transactions.show',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
        $what = strtolower($journal->transactionType->type);
        $breadcrumbs->parent('transactions.index', $what, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push($journal->description, route('transactions.show', [$journal->id]));
    }
);

Breadcrumbs::register(
    'transactions.convert.index',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionType $destinationType, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push(
            trans('firefly.convert_to_' . $destinationType->type, ['description' => $journal->description]),
            route('transactions.convert.index', [strtolower($destinationType->type), $journal->id])
        );
    }
);

// MASS TRANSACTION EDIT / DELETE
Breadcrumbs::register(
    'transactions.mass.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Collection $journals): void {
        if ($journals->count() > 0) {
            $journalIds = $journals->pluck('id')->toArray();
            $what       = strtolower($journals->first()->transactionType->type);
            $breadcrumbs->parent('transactions.index', $what, '(nothing)', new Carbon, new Carbon);
            $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.edit', $journalIds));

            return;
        }

        $breadcrumbs->parent('index');

        return;
    }
);

Breadcrumbs::register(
    'transactions.mass.delete',
    function (BreadCrumbsGenerator $breadcrumbs, Collection $journals) {
        $journalIds = $journals->pluck('id')->toArray();
        $what       = strtolower($journals->first()->transactionType->type);
        $breadcrumbs->parent('transactions.index', $what, '(nothing)', new Carbon, new Carbon);
        $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.delete', $journalIds));
    }
);

// BULK EDIT
Breadcrumbs::register(
    'transactions.bulk.edit',
    function (BreadCrumbsGenerator $breadcrumbs, Collection $journals): void {
        if ($journals->count() > 0) {
            $journalIds = $journals->pluck('id')->toArray();
            $what       = strtolower($journals->first()->transactionType->type);
            $breadcrumbs->parent('transactions.index', $what, '(nothing)', new Carbon, new Carbon);
            $breadcrumbs->push(trans('firefly.mass_bulk_journals'), route('transactions.bulk.edit', $journalIds));

            return;
        }

        $breadcrumbs->parent('index');

        return;
    }
);

// SPLIT
Breadcrumbs::register(
    'transactions.split.edit',
    function (BreadCrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push(trans('breadcrumbs.edit_journal', ['description' => $journal->description]), route('transactions.split.edit', [$journal->id]));
    }
);
