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
use DaveJamesMiller\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\User;
use Illuminate\Support\Arr;

if (!function_exists('limitStringLength')) {
    /**
     * Cuts away the middle of a string when it's very long.
     *
     * @param string $string
     *
     * @return string
     */
    function limitStringLength(string $string): string
    {
        $maxChars = 75;
        $length   = \strlen($string);
        $result   = $string;
        if ($length > $maxChars) {
            $result = substr_replace($string, ' ... ', $maxChars / 2, $length - $maxChars);
        }

        return $result;
    }
}

try {
    // HOME
    Breadcrumbs::register(
        'home',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
        }
    );

    Breadcrumbs::register(
        'index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
        }
    );

    // ACCOUNTS
    Breadcrumbs::register(
        'accounts.index',
        function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.' . strtolower(e($what)) . '_accounts'), route('accounts.index', [$what]));
        }
    );

    Breadcrumbs::register(
        'accounts.create',
        function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('accounts.index', $what);
            $breadcrumbs->push(trans('firefly.new_' . strtolower(e($what)) . '_account'), route('accounts.create', [$what]));
        }
    );

    Breadcrumbs::register(
        'accounts.show',
        function (BreadcrumbsGenerator $breadcrumbs, Account $account, Carbon $start = null, Carbon $end = null) {
            $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

            $breadcrumbs->parent('accounts.index', $what);
            $breadcrumbs->push(limitStringLength($account->name), route('accounts.show.all', [$account->id]));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start ? $start->formatLocalized((string)trans('config.month_and_day')) : '',
                     'end'   => $end ? $end->formatLocalized((string)trans('config.month_and_day')) : '',]
                );
                $breadcrumbs->push($title, route('accounts.show', $account));
            }
        }
    );

    Breadcrumbs::register(
        'accounts.show.all',
        function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

            $breadcrumbs->parent('accounts.index', $what);
            $breadcrumbs->push(limitStringLength($account->name), route('accounts.show', [$account->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.reconcile',
        function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $breadcrumbs->parent('accounts.show', $account);
            $breadcrumbs->push(trans('firefly.reconcile_account', ['account' => $account->name]), route('accounts.reconcile', [$account->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.reconcile.show',
        function (BreadcrumbsGenerator $breadcrumbs, Account $account, TransactionJournal $journal) {
            $breadcrumbs->parent('accounts.show', $account);
            $title = trans('firefly.reconciliation') . ' "' . $journal->description . '"';
            $breadcrumbs->push($title, route('accounts.reconcile.show', [$journal->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $breadcrumbs->parent('accounts.show', $account);
            $breadcrumbs->push(trans('firefly.delete_account', ['name' => limitStringLength($account->name)]), route('accounts.delete', [$account->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $breadcrumbs->parent('accounts.show', $account);
            $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

            $breadcrumbs->push(
                trans('firefly.edit_' . $what . '_account', ['name' => limitStringLength($account->name)]), route('accounts.edit', [$account->id])
            );
        }
    );

    // ADMIN
    Breadcrumbs::register(
        'admin.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.administration'), route('admin.index'));
        }
    );

    Breadcrumbs::register(
        'admin.users',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.list_all_users'), route('admin.users'));
        }
    );

    Breadcrumbs::register(
        'admin.users.show',
        function (BreadcrumbsGenerator $breadcrumbs, User $user) {
            $breadcrumbs->parent('admin.users');
            $breadcrumbs->push(trans('firefly.single_user_administration', ['email' => $user->email]), route('admin.users.show', [$user->id]));
        }
    );
    Breadcrumbs::register(
        'admin.users.edit',
        function (BreadcrumbsGenerator $breadcrumbs, User $user) {
            $breadcrumbs->parent('admin.users');
            $breadcrumbs->push(trans('firefly.edit_user', ['email' => $user->email]), route('admin.users.edit', [$user->id]));
        }
    );
    Breadcrumbs::register(
        'admin.users.delete',
        function (BreadcrumbsGenerator $breadcrumbs, User $user) {
            $breadcrumbs->parent('admin.users');
            $breadcrumbs->push(trans('firefly.delete_user', ['email' => $user->email]), route('admin.users.delete', [$user->id]));
        }
    );

    Breadcrumbs::register(
        'admin.users.domains',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.blocked_domains'), route('admin.users.domains'));
        }
    );

    Breadcrumbs::register(
        'admin.configuration.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.instance_configuration'), route('admin.configuration.index'));
        }
    );
    Breadcrumbs::register(
        'admin.update-check',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.update_check_title'), route('admin.update-check'));
        }
    );

    Breadcrumbs::register(
        'admin.links.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.journal_link_configuration'), route('admin.links.index'));
        }
    );

    Breadcrumbs::register(
        'admin.links.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.create_new_link_type'), route('admin.links.create'));
        }
    );

    Breadcrumbs::register(
        'admin.links.show',
        function (BreadcrumbsGenerator $breadcrumbs, LinkType $linkType) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.overview_for_link', ['name' => limitStringLength($linkType->name)]), route('admin.links.show', [$linkType->id]));
        }
    );

    Breadcrumbs::register(
        'admin.links.edit',
        function (BreadcrumbsGenerator $breadcrumbs, LinkType $linkType) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.edit_link_type', ['name' => limitStringLength($linkType->name)]), route('admin.links.edit', [$linkType->id]));
        }
    );

    Breadcrumbs::register(
        'admin.links.delete',
        function (BreadcrumbsGenerator $breadcrumbs, LinkType $linkType) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.delete_link_type', ['name' => limitStringLength($linkType->name)]), route('admin.links.delete', [$linkType->id]));
        }
    );

    Breadcrumbs::register(
        'transactions.link.delete',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionJournalLink $link) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.delete_journal_link'), route('transactions.link.delete', $link->id));
        }
    );

    // ATTACHMENTS
    Breadcrumbs::register(
        'attachments.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.attachments'), route('attachments.index'));
        }
    );

    Breadcrumbs::register(
        'attachments.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Attachment $attachment) {
            $object = $attachment->attachable;
            if ($object instanceof TransactionJournal) {
                $group = $object->transactionGroup;
                if (null !== $group && $group instanceof TransactionGroup) {
                    $breadcrumbs->parent('transactions.show', $object->transactionGroup);
                }
            }

            if ($object instanceof Bill) {
                $breadcrumbs->parent('bills.show', $object);
            }
            $breadcrumbs->push(limitStringLength($attachment->filename), route('attachments.edit', [$attachment]));
        }
    );
    Breadcrumbs::register(
        'attachments.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Attachment $attachment) {
            $object = $attachment->attachable;
            if ($object instanceof TransactionJournal) {
                $breadcrumbs->parent('transactions.show', $object->transactionGroup);
            }
            if ($object instanceof Bill) {
                $breadcrumbs->parent('bills.show', $object);
            }
            $breadcrumbs->push(
                trans('firefly.delete_attachment', ['name' => limitStringLength($attachment->filename)]), route('attachments.edit', [$attachment])
            );
        }
    );

    // BILLS
    Breadcrumbs::register(
        'bills.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.bills'), route('bills.index'));
        }
    );
    Breadcrumbs::register(
        'bills.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('bills.index');
            $breadcrumbs->push(trans('breadcrumbs.newBill'), route('bills.create'));
        }
    );

    Breadcrumbs::register(
        'bills.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Bill $bill) {
            $breadcrumbs->parent('bills.show', $bill);
            $breadcrumbs->push(trans('breadcrumbs.edit_bill', ['name' => limitStringLength($bill->name)]), route('bills.edit', [$bill->id]));
        }
    );
    Breadcrumbs::register(
        'bills.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Bill $bill) {
            $breadcrumbs->parent('bills.show', $bill);
            $breadcrumbs->push(trans('breadcrumbs.delete_bill', ['name' => limitStringLength($bill->name)]), route('bills.delete', [$bill->id]));
        }
    );

    Breadcrumbs::register(
        'bills.show',
        function (BreadcrumbsGenerator $breadcrumbs, Bill $bill) {
            $breadcrumbs->parent('bills.index');
            $breadcrumbs->push(limitStringLength($bill->name), route('bills.show', [$bill->id]));
        }
    );

    // BUDGETS
    Breadcrumbs::register(
        'budgets.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.budgets'), route('budgets.index'));
        }
    );
    Breadcrumbs::register(
        'budgets.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(trans('firefly.create_new_budget'), route('budgets.create'));
        }
    );

    Breadcrumbs::register(
        'budgets.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Budget $budget) {
            $breadcrumbs->parent('budgets.show', $budget);
            $breadcrumbs->push(trans('firefly.edit_budget', ['name' => limitStringLength($budget->name)]), route('budgets.edit', [$budget->id]));
        }
    );
    Breadcrumbs::register(
        'budgets.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Budget $budget) {
            $breadcrumbs->parent('budgets.show', $budget);
            $breadcrumbs->push(trans('firefly.delete_budget', ['name' => limitStringLength($budget->name)]), route('budgets.delete', [$budget->id]));
        }
    );

    Breadcrumbs::register(
        'budgets.no-budget',
        function (BreadcrumbsGenerator $breadcrumbs, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string)trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string)trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('budgets.no-budget'));
            }
        }
    );

    Breadcrumbs::register(
        'budgets.no-budget-all',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));
            $breadcrumbs->push(trans('firefly.everything'), route('budgets.no-budget-all'));
        }
    );

    Breadcrumbs::register(
        'budgets.show',
        function (BreadcrumbsGenerator $breadcrumbs, Budget $budget) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(limitStringLength($budget->name), route('budgets.show', [$budget->id]));
            $breadcrumbs->push(trans('firefly.everything'), route('budgets.show', [$budget->id]));
        }
    );

    Breadcrumbs::register(
        'budgets.show.limit',
        function (BreadcrumbsGenerator $breadcrumbs, Budget $budget, BudgetLimit $budgetLimit) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(limitStringLength($budget->name), route('budgets.show', [$budget->id]));

            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $budgetLimit->start_date->formatLocalized((string)trans('config.month_and_day')),
                 'end'   => $budgetLimit->end_date->formatLocalized((string)trans('config.month_and_day')),]
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
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.categories'), route('categories.index'));
        }
    );
    Breadcrumbs::register(
        'categories.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(trans('firefly.new_category'), route('categories.create'));
        }
    );

    Breadcrumbs::register(
        'categories.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Category $category) {
            $breadcrumbs->parent('categories.show.all', $category);
            $breadcrumbs->push(trans('firefly.edit_category', ['name' => limitStringLength($category->name)]), route('categories.edit', [$category->id]));
        }
    );
    Breadcrumbs::register(
        'categories.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Category $category) {
            $breadcrumbs->parent('categories.show', $category);
            $breadcrumbs->push(trans('firefly.delete_category', ['name' => limitStringLength($category->name)]), route('categories.delete', [$category->id]));
        }
    );

    Breadcrumbs::register(
        'categories.show',
        function (BreadcrumbsGenerator $breadcrumbs, Category $category, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(limitStringLength($category->name), route('categories.show', [$category->id]));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string)trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string)trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('categories.show', [$category->id]));
            }
        }
    );

    Breadcrumbs::register(
        'categories.show.all',
        function (BreadcrumbsGenerator $breadcrumbs, Category $category) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(limitStringLength($category->name), route('categories.show', [$category->id]));
            $breadcrumbs->push(trans('firefly.everything'), route('categories.show.all', [$category->id]));
        }
    );

    Breadcrumbs::register(
        'categories.no-category',
        function (BreadcrumbsGenerator $breadcrumbs, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string)trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string)trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('categories.no-category'));
            }
        }
    );


    Breadcrumbs::register(
        'categories.no-category.all',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));
            $breadcrumbs->push(trans('firefly.everything'), route('categories.no-category.all'));
        }
    );

    // CURRENCIES
    Breadcrumbs::register(
        'currencies.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.currencies'), route('currencies.index'));
        }
    );

    Breadcrumbs::register(
        'currencies.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('currencies.index');
            $breadcrumbs->push(trans('firefly.create_currency'), route('currencies.create'));
        }
    );

    Breadcrumbs::register(
        'currencies.edit',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionCurrency $currency) {
            $breadcrumbs->parent('currencies.index');
            $breadcrumbs->push(trans('breadcrumbs.edit_currency', ['name' => $currency->name]), route('currencies.edit', [$currency->id]));
        }
    );
    Breadcrumbs::register(
        'currencies.delete',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionCurrency $currency) {
            $breadcrumbs->parent('currencies.index');
            $breadcrumbs->push(trans('breadcrumbs.delete_currency', ['name' => $currency->name]), route('currencies.delete', [$currency->id]));
        }
    );

    // EXPORT
    Breadcrumbs::register(
        'export.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.export_data'), route('export.index'));
        }
    );

    // PIGGY BANKS
    Breadcrumbs::register(
        'piggy-banks.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.piggyBanks'), route('piggy-banks.index'));
        }
    );
    Breadcrumbs::register(
        'piggy-banks.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('piggy-banks.index');
            $breadcrumbs->push(trans('breadcrumbs.newPiggyBank'), route('piggy-banks.create'));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.edit',
        function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(trans('breadcrumbs.edit_piggyBank', ['name' => $piggyBank->name]), route('piggy-banks.edit', [$piggyBank->id]));
        }
    );
    Breadcrumbs::register(
        'piggy-banks.delete',
        function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]), route('piggy-banks.delete', [$piggyBank->id]));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.show',
        function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.index');
            $breadcrumbs->push($piggyBank->name, route('piggy-banks.show', [$piggyBank->id]));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.add-money-mobile',
        function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(trans('firefly.add_money_to_piggy', ['name' => $piggyBank->name]), route('piggy-banks.add-money-mobile', [$piggyBank->id]));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.remove-money-mobile',
        function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
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
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.import_index_title'), route('import.index'));
        }
    );

    Breadcrumbs::register(
        'import.prerequisites.index',
        function (BreadcrumbsGenerator $breadcrumbs, string $importProvider) {
            $breadcrumbs->parent('import.index');
            $breadcrumbs->push(trans('import.prerequisites_breadcrumb_' . $importProvider), route('import.prerequisites.index', [$importProvider]));
        }
    );

    Breadcrumbs::register(
        'import.job.configuration.index',
        function (BreadcrumbsGenerator $breadcrumbs, ImportJob $job) {
            $breadcrumbs->parent('import.index');
            $breadcrumbs->push(trans('import.job_configuration_breadcrumb', ['key' => $job->key]), route('import.job.configuration.index', [$job->key]));
        }
    );

    Breadcrumbs::register(
        'import.job.status.index',
        function (BreadcrumbsGenerator $breadcrumbs, ImportJob $job) {
            $breadcrumbs->parent('import.index');
            $breadcrumbs->push(trans('import.job_status_breadcrumb', ['key' => $job->key]), route('import.job.status.index', [$job->key]));
        }
    );


    // PREFERENCES
    Breadcrumbs::register(
        'preferences.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));
        }
    );

    Breadcrumbs::register(
        'profile.code',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
        }
    );

    Breadcrumbs::register(
        'profile.new-backup-codes',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
        }
    );

    // PROFILE
    Breadcrumbs::register(
        'profile.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
        }
    );
    Breadcrumbs::register(
        'profile.change-password',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('profile.index');
            $breadcrumbs->push(trans('breadcrumbs.changePassword'), route('profile.change-password'));
        }
    );

    Breadcrumbs::register(
        'profile.change-email',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('profile.index');
            $breadcrumbs->push(trans('breadcrumbs.change_email'), route('profile.change-email'));
        }
    );

    Breadcrumbs::register(
        'profile.delete-account',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('profile.index');
            $breadcrumbs->push(trans('firefly.delete_account'), route('profile.delete-account'));
        }
    );

    // REPORTS
    Breadcrumbs::register(
        'reports.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.reports'), route('reports.index'));
        }
    );

    Breadcrumbs::register(
        'reports.report.audit',
        function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
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
        function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $budgetIds, Carbon $start, Carbon $end) {
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
        function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $tagTags, Carbon $start, Carbon $end) {
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
        function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $categoryIds, Carbon $start, Carbon $end) {
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
        function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $expenseIds, Carbon $start, Carbon $end) {
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
        function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
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
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.getting_started'), route('new-user.index'));
        }
    );

    // Recurring transactions controller:
    Breadcrumbs::register(
        'recurring.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.recurrences'), route('recurring.index'));
        }
    );
    Breadcrumbs::register(
        'recurring.show',
        function (BreadcrumbsGenerator $breadcrumbs, Recurrence $recurrence) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push($recurrence->title, route('recurring.show', [$recurrence->id]));
        }
    );

    Breadcrumbs::register(
        'recurring.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Recurrence $recurrence) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.delete_recurring', ['title' => $recurrence->title]), route('recurring.delete', [$recurrence->id]));
        }
    );

    Breadcrumbs::register(
        'recurring.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Recurrence $recurrence) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.edit_recurrence', ['title' => $recurrence->title]), route('recurring.edit', [$recurrence->id]));
        }
    );

    Breadcrumbs::register(
        'recurring.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.create_new_recurrence'), route('recurring.create'));
        }
    );

    // Rules
    Breadcrumbs::register(
        'rules.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.rules'), route('rules.index'));
        }
    );

    Breadcrumbs::register(
        'rules.create',
        function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup = null) {
            $breadcrumbs->parent('rules.index');
            if (null === $ruleGroup) {
                $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
            }
            if (null !== $ruleGroup) {
                $breadcrumbs->push(trans('firefly.make_new_rule', ['title' => $ruleGroup->title]), route('rules.create', [$ruleGroup]));
            }

        }
    );

    Breadcrumbs::register(
        'rules.create-from-bill',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
        }
    );
    Breadcrumbs::register(
        'rules.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Rule $rule) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.edit_rule', ['title' => $rule->title]), route('rules.edit', [$rule]));
        }
    );
    Breadcrumbs::register(
        'rules.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Rule $rule) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.delete_rule', ['title' => $rule->title]), route('rules.delete', [$rule]));
        }
    );
    Breadcrumbs::register(
        'rule-groups.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.make_new_rule_group'), route('rule-groups.create'));
        }
    );
    Breadcrumbs::register(
        'rule-groups.edit',
        function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.edit', [$ruleGroup]));
        }
    );
    Breadcrumbs::register(
        'rule-groups.delete',
        function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.delete', [$ruleGroup]));
        }
    );

    Breadcrumbs::register(
        'rules.select-transactions',
        function (BreadcrumbsGenerator $breadcrumbs, Rule $rule) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(
                trans('firefly.rule_select_transactions', ['title' => $rule->title]), route('rules.select-transactions', [$rule])
            );
        }
    );

    Breadcrumbs::register(
        'rule-groups.select-transactions',
        function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(
                trans('firefly.rule_group_select_transactions', ['title' => $ruleGroup->title]), route('rule-groups.select-transactions', [$ruleGroup])
            );
        }
    );

    // SEARCH
    Breadcrumbs::register(
        'search.index',
        function (BreadcrumbsGenerator $breadcrumbs, $query) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.search_result', ['query' => $query]), route('search.index'));
        }
    );

    // TAGS
    Breadcrumbs::register(
        'tags.index',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.tags'), route('tags.index'));
        }
    );

    Breadcrumbs::register(
        'tags.create',
        function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('tags.index');
            $breadcrumbs->push(trans('breadcrumbs.createTag'), route('tags.create'));
        }
    );

    Breadcrumbs::register(
        'tags.edit',
        function (BreadcrumbsGenerator $breadcrumbs, Tag $tag) {
            $breadcrumbs->parent('tags.show', $tag);
            $breadcrumbs->push(trans('breadcrumbs.edit_tag', ['tag' => $tag->tag]), route('tags.edit', [$tag->id]));
        }
    );

    Breadcrumbs::register(
        'tags.delete',
        function (BreadcrumbsGenerator $breadcrumbs, Tag $tag) {
            $breadcrumbs->parent('tags.show', $tag);
            $breadcrumbs->push(trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]), route('tags.delete', [$tag->id]));
        }
    );

    Breadcrumbs::register(
        'tags.show',
        function (BreadcrumbsGenerator $breadcrumbs, Tag $tag, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('tags.index');

            $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id, $start, $end]));
            if (null !== $start && $end !== null) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string)trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string)trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('tags.show', [$tag->id, $start, $end]));
            }
        }
    );


    Breadcrumbs::register(
        'tags.show.all',
        function (BreadcrumbsGenerator $breadcrumbs, Tag $tag) {
            $breadcrumbs->parent('tags.index');
            $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id]));
            $title = (string)trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
            $breadcrumbs->push($title, route('tags.show.all', $tag->id));
        }
    );

    // TRANSACTIONS

    Breadcrumbs::register(
        'transactions.index',
        function (BreadcrumbsGenerator $breadcrumbs, string $what, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));

            if (null !== $start && null !== $end) {
                // add date range:
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string)trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string)trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('transactions.index', [$what, $start, $end]));
            }
        }
    );

    Breadcrumbs::register(
        'transactions.index.all',
        function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));
        }
    );

    Breadcrumbs::register(
        'transactions.create',
        function (BreadcrumbsGenerator $breadcrumbs, string $objectType) {
            $breadcrumbs->parent('transactions.index', $objectType);
            $breadcrumbs->push(trans('breadcrumbs.create_new_transaction'), route('transactions.create', [$objectType]));
        }
    );

    Breadcrumbs::register(
        'transactions.edit',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionGroup $group) {
            $breadcrumbs->parent('transactions.show', $group);

            /** @var TransactionJournal $first */
            $first = $group->transactionJournals()->first();

            $breadcrumbs->push(
                trans('breadcrumbs.edit_journal', ['description' => limitStringLength($first->description)]), route('transactions.edit', [$group->id])
            );
        }
    );

    // also edit reconciliations:
    Breadcrumbs::register(
        'accounts.reconcile.edit',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
            $breadcrumbs->parent('transactions.show', $journal);
            $breadcrumbs->push(
                trans('breadcrumbs.edit_reconciliation', ['description' => limitStringLength($journal->description)]),
                route('accounts.reconcile.edit', [$journal->id])
            );
        }
    );

    Breadcrumbs::register(
        'transactions.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionGroup $group) {
            $breadcrumbs->parent('transactions.show', $group);

            $journal  = $group->transactionJournals->first();
            $breadcrumbs->push(
                trans('breadcrumbs.delete_group', ['description' => limitStringLength($group->title ?? $journal->description)]),
                route('transactions.delete', [$group->id])
            );
        }
    );

    Breadcrumbs::register(
        'transactions.show',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionGroup $group) {
            /** @var TransactionJournal $first */
            $first = $group->transactionJournals()->first();
            $type  = strtolower($first->transactionType->type);
            $title = limitStringLength($first->description);
            if ($group->transactionJournals()->count() > 1) {
                $title = limitStringLength($group->title);
            }
            if('opening balance' === $type) {
                
                $breadcrumbs->push($title, route('transactions.show', [$group->id]));
                return;
            }

            $breadcrumbs->parent('transactions.index', $type);
            $breadcrumbs->push($title, route('transactions.show', [$group->id]));
        }
    );

    Breadcrumbs::register(
        'transactions.convert.index',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionGroup $group, string $groupTitle) {
            $breadcrumbs->parent('transactions.show', $group);
            $breadcrumbs->push(
                trans('firefly.breadcrumb_convert_group', ['description' => limitStringLength($groupTitle)]),
                route('transactions.convert.index', [$group->id, 'something'])
            );
        }
    );

    // MASS TRANSACTION EDIT / DELETE
    Breadcrumbs::register(
        'transactions.mass.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, array $journals): void {
            if (count($journals) > 0) {
                $objectType = strtolower(reset($journals)['transaction_type_type']);
                $breadcrumbs->parent('transactions.index', $objectType);
                $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.edit', ['']));

                return;
            }
            $breadcrumbs->parent('index');
        }
    );

    Breadcrumbs::register(
        'transactions.mass.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, array $journals) {
            $objectType= strtolower(reset($journals)['transaction_type_type']);
            $breadcrumbs->parent('transactions.index', $objectType);
            $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.delete', ['']));
        }
    );

    // BULK EDIT
    Breadcrumbs::register(
        'transactions.bulk.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, array $journals): void {
            if (count($journals) > 0) {
                $ids   = Arr::pluck($journals, 'transaction_journal_id');
                $first = reset($journals);
                $breadcrumbs->parent('transactions.index', strtolower($first['transaction_type_type']));
                $breadcrumbs->push(trans('firefly.mass_bulk_journals'), route('transactions.bulk.edit', $ids));

                return;
            }

            $breadcrumbs->parent('index');
        }
    );

    // SPLIT
    Breadcrumbs::register(
        'transactions.split.edit',
        function (BreadcrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
            $breadcrumbs->parent('transactions.show', $journal);
            $breadcrumbs->push(trans('breadcrumbs.edit_journal', ['description' => $journal->description]), route('transactions.split.edit', [$journal->id]));
        }
    );
} catch (DuplicateBreadcrumbException $e) {
}
