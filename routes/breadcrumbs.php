<?php
/**
 * breadcrumbs.php
 * Copyright (c) 2019 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
use FireflyIII\Models\LinkType;
use FireflyIII\Models\ObjectGroup;
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
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
        }
    );

    Breadcrumbs::register(
        'index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
        }
    );

    // ACCOUNTS
    Breadcrumbs::register(
        'accounts.index',
        static function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.' . strtolower(e($what)) . '_accounts'), route('accounts.index', [$what]));
        }
    );
    Breadcrumbs::register( // inactive
        'accounts.inactive.index',
        static function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.' . strtolower(e($what)) . '_accounts_inactive'), route('accounts.inactive.index', [$what]));
        }
    );

    Breadcrumbs::register(
        'accounts.create',
        static function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('accounts.index', $what);
            $breadcrumbs->push(trans('firefly.new_' . strtolower(e($what)) . '_account'), route('accounts.create', [$what]));
        }
    );

    Breadcrumbs::register(
        'accounts.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Account $account, Carbon $start = null, Carbon $end = null) {
            $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

            $breadcrumbs->parent('accounts.index', $what);
            $breadcrumbs->push(limitStringLength($account->name), route('accounts.show.all', [$account->id]));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' =>$start->formatLocalized((string) trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string) trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('accounts.show', $account));
            }
        }
    );

    Breadcrumbs::register(
        'accounts.show.all',
        static function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

            $breadcrumbs->parent('accounts.index', $what);
            $breadcrumbs->push(limitStringLength($account->name), route('accounts.show', [$account->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.reconcile',
        static function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $breadcrumbs->parent('accounts.show', $account);
            $breadcrumbs->push(trans('firefly.reconcile_account', ['account' => $account->name]), route('accounts.reconcile', [$account->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.reconcile.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Account $account, TransactionJournal $journal) {
            $breadcrumbs->parent('accounts.show', $account);
            $title = trans('firefly.reconciliation') . ' "' . $journal->description . '"';
            $breadcrumbs->push($title, route('accounts.reconcile.show', [$journal->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $breadcrumbs->parent('accounts.show', $account);
            $breadcrumbs->push(trans('firefly.delete_account', ['name' => limitStringLength($account->name)]), route('accounts.delete', [$account->id]));
        }
    );

    Breadcrumbs::register(
        'accounts.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Account $account) {
            $breadcrumbs->parent('accounts.show', $account);
            $what = config('firefly.shortNamesByFullName.' . $account->accountType->type);

            $breadcrumbs->push(
                trans('firefly.edit_' . $what . '_account', ['name' => limitStringLength($account->name)]),
                route('accounts.edit', [$account->id])
            );
        }
    );

    // ADMIN
    Breadcrumbs::register(
        'admin.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.administration'), route('admin.index'));
        }
    );

    Breadcrumbs::register(
        'admin.users',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.list_all_users'), route('admin.users'));
        }
    );

    Breadcrumbs::register(
        'admin.users.show',
        static function (BreadcrumbsGenerator $breadcrumbs, User $user) {
            $breadcrumbs->parent('admin.users');
            $breadcrumbs->push(trans('firefly.single_user_administration', ['email' => $user->email]), route('admin.users.show', [$user->id]));
        }
    );
    Breadcrumbs::register(
        'admin.users.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, User $user) {
            $breadcrumbs->parent('admin.users');
            $breadcrumbs->push(trans('firefly.edit_user', ['email' => $user->email]), route('admin.users.edit', [$user->id]));
        }
    );
    Breadcrumbs::register(
        'admin.users.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, User $user) {
            $breadcrumbs->parent('admin.users');
            $breadcrumbs->push(trans('firefly.delete_user', ['email' => $user->email]), route('admin.users.delete', [$user->id]));
        }
    );

    Breadcrumbs::register(
        'admin.users.domains',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.blocked_domains'), route('admin.users.domains'));
        }
    );

    Breadcrumbs::register(
        'admin.configuration.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.instance_configuration'), route('admin.configuration.index'));
        }
    );
    Breadcrumbs::register(
        'admin.update-check',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.update_check_title'), route('admin.update-check'));
        }
    );

    Breadcrumbs::register(
        'admin.links.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('firefly.journal_link_configuration'), route('admin.links.index'));
        }
    );

    Breadcrumbs::register(
        'admin.links.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.create_new_link_type'), route('admin.links.create'));
        }
    );

    Breadcrumbs::register(
        'admin.links.show',
        static function (BreadcrumbsGenerator $breadcrumbs, LinkType $linkType) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.overview_for_link', ['name' => limitStringLength($linkType->name)]), route('admin.links.show', [$linkType->id]));
        }
    );

    Breadcrumbs::register(
        'admin.links.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, LinkType $linkType) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.edit_link_type', ['name' => limitStringLength($linkType->name)]), route('admin.links.edit', [$linkType->id]));
        }
    );

    Breadcrumbs::register(
        'admin.links.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, LinkType $linkType) {
            $breadcrumbs->parent('admin.links.index');
            $breadcrumbs->push(trans('firefly.delete_link_type', ['name' => limitStringLength($linkType->name)]), route('admin.links.delete', [$linkType->id]));
        }
    );

    Breadcrumbs::register(
        'admin.telemetry.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.index');
            $breadcrumbs->push(trans('breadcrumbs.telemetry_index'), route('admin.telemetry.index'));
        }
    );

    Breadcrumbs::register(
        'admin.telemetry.view',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('admin.telemetry.index');
            $breadcrumbs->push(trans('breadcrumbs.telemetry_view'));
        }
    );

    Breadcrumbs::register(
        'transactions.link.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionJournalLink $link) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.delete_journal_link'), route('transactions.link.delete', $link->id));
        }
    );

    // ATTACHMENTS
    Breadcrumbs::register(
        'attachments.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.attachments'), route('attachments.index'));
        }
    );

    Breadcrumbs::register(
        'attachments.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Attachment $attachment) {
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
            $breadcrumbs->push(
                limitStringLength(trans('firefly.edit_attachment', ['name' => $attachment->filename])),
                route('attachments.edit', [$attachment])
            );
        }
    );
    Breadcrumbs::register(
        'attachments.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Attachment $attachment) {
            $object = $attachment->attachable;
            if ($object instanceof TransactionJournal) {
                $breadcrumbs->parent('transactions.show', $object->transactionGroup);
            }
            if ($object instanceof Bill) {
                $breadcrumbs->parent('bills.show', $object);
            }
            $breadcrumbs->push(
                trans('firefly.delete_attachment', ['name' => limitStringLength($attachment->filename)]),
                route('attachments.edit', [$attachment])
            );
        }
    );

    // BILLS
    Breadcrumbs::register(
        'bills.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.bills'), route('bills.index'));
        }
    );
    Breadcrumbs::register(
        'bills.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('bills.index');
            $breadcrumbs->push(trans('breadcrumbs.newBill'), route('bills.create'));
        }
    );

    Breadcrumbs::register(
        'bills.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Bill $bill) {
            $breadcrumbs->parent('bills.show', $bill);
            $breadcrumbs->push(trans('breadcrumbs.edit_bill', ['name' => limitStringLength($bill->name)]), route('bills.edit', [$bill->id]));
        }
    );
    Breadcrumbs::register(
        'bills.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Bill $bill) {
            $breadcrumbs->parent('bills.show', $bill);
            $breadcrumbs->push(trans('breadcrumbs.delete_bill', ['name' => limitStringLength($bill->name)]), route('bills.delete', [$bill->id]));
        }
    );

    Breadcrumbs::register(
        'bills.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Bill $bill) {
            $breadcrumbs->parent('bills.index');
            $breadcrumbs->push(limitStringLength($bill->name), route('bills.show', [$bill->id]));
        }
    );

    // BUDGETS
    Breadcrumbs::register(
        'budgets.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.budgets'), route('budgets.index'));
        }
    );
    Breadcrumbs::register(
        'budgets.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(trans('firefly.create_new_budget'), route('budgets.create'));
        }
    );

    Breadcrumbs::register(
        'budgets.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Budget $budget) {
            $breadcrumbs->parent('budgets.show', $budget);
            $breadcrumbs->push(trans('firefly.edit_budget', ['name' => limitStringLength($budget->name)]), route('budgets.edit', [$budget->id]));
        }
    );
    Breadcrumbs::register(
        'budgets.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Budget $budget) {
            $breadcrumbs->parent('budgets.show', $budget);
            $breadcrumbs->push(trans('firefly.delete_budget', ['name' => limitStringLength($budget->name)]), route('budgets.delete', [$budget->id]));
        }
    );

    Breadcrumbs::register(
        'budgets.no-budget',
        static function (BreadcrumbsGenerator $breadcrumbs, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string) trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string) trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('budgets.no-budget'));
            }
        }
    );

    Breadcrumbs::register(
        'budgets.no-budget-all',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));
            $breadcrumbs->push(trans('firefly.everything'), route('budgets.no-budget-all'));
        }
    );

    Breadcrumbs::register(
        'budgets.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Budget $budget) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(limitStringLength($budget->name), route('budgets.show', [$budget->id]));
            $breadcrumbs->push(trans('firefly.everything'), route('budgets.show', [$budget->id]));
        }
    );

    Breadcrumbs::register(
        'budgets.show.limit',
        static function (BreadcrumbsGenerator $breadcrumbs, Budget $budget, BudgetLimit $budgetLimit) {
            $breadcrumbs->parent('budgets.index');
            $breadcrumbs->push(limitStringLength($budget->name), route('budgets.show', [$budget->id]));

            $title = trans(
                'firefly.between_dates_breadcrumb',
                ['start' => $budgetLimit->start_date->formatLocalized((string) trans('config.month_and_day')),
                 'end'   => $budgetLimit->end_date->formatLocalized((string) trans('config.month_and_day')),]
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
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.categories'), route('categories.index'));
        }
    );
    Breadcrumbs::register(
        'categories.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(trans('firefly.new_category'), route('categories.create'));
        }
    );

    Breadcrumbs::register(
        'categories.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Category $category) {
            $breadcrumbs->parent('categories.show.all', $category);
            $breadcrumbs->push(trans('firefly.edit_category', ['name' => limitStringLength($category->name)]), route('categories.edit', [$category->id]));
        }
    );
    Breadcrumbs::register(
        'categories.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Category $category) {
            $breadcrumbs->parent('categories.show', $category);
            $breadcrumbs->push(trans('firefly.delete_category', ['name' => limitStringLength($category->name)]), route('categories.delete', [$category->id]));
        }
    );

    Breadcrumbs::register(
        'categories.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Category $category, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(limitStringLength($category->name), route('categories.show', [$category->id]));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string) trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string) trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('categories.show', [$category->id]));
            }
        }
    );

    Breadcrumbs::register(
        'categories.show.all',
        static function (BreadcrumbsGenerator $breadcrumbs, Category $category) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(limitStringLength($category->name), route('categories.show', [$category->id]));
            $breadcrumbs->push(trans('firefly.everything'), route('categories.show.all', [$category->id]));
        }
    );

    Breadcrumbs::register(
        'categories.no-category',
        static function (BreadcrumbsGenerator $breadcrumbs, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string) trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string) trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('categories.no-category'));
            }
        }
    );

    Breadcrumbs::register(
        'categories.no-category.all',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('categories.index');
            $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));
            $breadcrumbs->push(trans('firefly.everything'), route('categories.no-category.all'));
        }
    );

    // CURRENCIES
    Breadcrumbs::register(
        'currencies.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.currencies'), route('currencies.index'));
        }
    );

    Breadcrumbs::register(
        'currencies.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('currencies.index');
            $breadcrumbs->push(trans('firefly.create_currency'), route('currencies.create'));
        }
    );

    Breadcrumbs::register(
        'currencies.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionCurrency $currency) {
            $breadcrumbs->parent('currencies.index');
            $breadcrumbs->push(trans('breadcrumbs.edit_currency', ['name' => $currency->name]), route('currencies.edit', [$currency->id]));
        }
    );
    Breadcrumbs::register(
        'currencies.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionCurrency $currency) {
            $breadcrumbs->parent('currencies.index');
            $breadcrumbs->push(trans('breadcrumbs.delete_currency', ['name' => $currency->name]), route('currencies.delete', [$currency->id]));
        }
    );

    // EXPORT
    Breadcrumbs::register(
        'export.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.export_data_bc'), route('export.index'));
        }
    );

    // PIGGY BANKS
    Breadcrumbs::register(
        'piggy-banks.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.piggyBanks'), route('piggy-banks.index'));
        }
    );
    Breadcrumbs::register(
        'piggy-banks.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('piggy-banks.index');
            $breadcrumbs->push(trans('breadcrumbs.newPiggyBank'), route('piggy-banks.create'));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(trans('breadcrumbs.edit_piggyBank', ['name' => $piggyBank->name]), route('piggy-banks.edit', [$piggyBank->id]));
        }
    );
    Breadcrumbs::register(
        'piggy-banks.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]), route('piggy-banks.delete', [$piggyBank->id]));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.show',
        static function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.index');
            $breadcrumbs->push($piggyBank->name, route('piggy-banks.show', [$piggyBank->id]));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.add-money-mobile',
        static function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(trans('firefly.add_money_to_piggy', ['name' => $piggyBank->name]), route('piggy-banks.add-money-mobile', [$piggyBank->id]));
        }
    );

    Breadcrumbs::register(
        'piggy-banks.remove-money-mobile',
        static function (BreadcrumbsGenerator $breadcrumbs, PiggyBank $piggyBank) {
            $breadcrumbs->parent('piggy-banks.show', $piggyBank);
            $breadcrumbs->push(
                trans('firefly.remove_money_from_piggy_title', ['name' => $piggyBank->name]),
                route('piggy-banks.remove-money-mobile', [$piggyBank->id])
            );
        }
    );

    // PREFERENCES
    Breadcrumbs::register(
        'preferences.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));
        }
    );

    Breadcrumbs::register(
        'profile.code',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
        }
    );

    Breadcrumbs::register(
        'profile.new-backup-codes',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
        }
    );

    Breadcrumbs::register(
        'profile.logout-others',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.logout_others'), route('profile.logout-others'));
        }
    );

    // PROFILE
    Breadcrumbs::register(
        'profile.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
        }
    );
    Breadcrumbs::register(
        'profile.change-password',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('profile.index');
            $breadcrumbs->push(trans('breadcrumbs.changePassword'), route('profile.change-password'));
        }
    );

    Breadcrumbs::register(
        'profile.change-email',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('profile.index');
            $breadcrumbs->push(trans('breadcrumbs.change_email'), route('profile.change-email'));
        }
    );

    Breadcrumbs::register(
        'profile.delete-account',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('profile.index');
            $breadcrumbs->push(trans('firefly.delete_account'), route('profile.delete-account'));
        }
    );

    // REPORTS
    Breadcrumbs::register(
        'reports.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.reports'), route('reports.index'));
        }
    );

    Breadcrumbs::register(
        'reports.report.audit',
        static function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
            $breadcrumbs->parent('reports.index');

            $monthFormat = (string) trans('config.month_and_day');
            $startString = $start->formatLocalized($monthFormat);
            $endString   = $end->formatLocalized($monthFormat);
            $title       = (string) trans('firefly.report_audit', ['start' => $startString, 'end' => $endString]);

            $breadcrumbs->push($title, route('reports.report.audit', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
        }
    );
    Breadcrumbs::register(
        'reports.report.budget',
        static function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $budgetIds, Carbon $start, Carbon $end) {
            $breadcrumbs->parent('reports.index');

            $monthFormat = (string) trans('config.month_and_day');
            $startString = $start->formatLocalized($monthFormat);
            $endString   = $end->formatLocalized($monthFormat);
            $title       = (string) trans('firefly.report_budget', ['start' => $startString, 'end' => $endString]);

            $breadcrumbs->push($title, route('reports.report.budget', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]));
        }
    );

    Breadcrumbs::register(
        'reports.report.tag',
        static function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $tagTags, Carbon $start, Carbon $end) {
            $breadcrumbs->parent('reports.index');

            $monthFormat = (string) trans('config.month_and_day');
            $startString = $start->formatLocalized($monthFormat);
            $endString   = $end->formatLocalized($monthFormat);
            $title       = (string) trans('firefly.report_tag', ['start' => $startString, 'end' => $endString]);

            $breadcrumbs->push($title, route('reports.report.tag', [$accountIds, $tagTags, $start->format('Ymd'), $end->format('Ymd')]));
        }
    );

    Breadcrumbs::register(
        'reports.report.category',
        static function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $categoryIds, Carbon $start, Carbon $end) {
            $breadcrumbs->parent('reports.index');

            $monthFormat = (string) trans('config.month_and_day');
            $startString = $start->formatLocalized($monthFormat);
            $endString   = $end->formatLocalized($monthFormat);
            $title       = (string) trans('firefly.report_category', ['start' => $startString, 'end' => $endString]);

            $breadcrumbs->push($title, route('reports.report.category', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]));
        }
    );

    Breadcrumbs::register(
        'reports.report.double',
        static function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, string $doubleIds, Carbon $start, Carbon $end) {
            $breadcrumbs->parent('reports.index');

            $monthFormat = (string) trans('config.month_and_day');
            $startString = $start->formatLocalized($monthFormat);
            $endString   = $end->formatLocalized($monthFormat);
            $title       = (string) trans('firefly.report_double', ['start' => $startString, 'end' => $endString]);

            $breadcrumbs->push($title, route('reports.report.double', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]));
        }
    );

    Breadcrumbs::register(
        'reports.report.default',
        static function (BreadcrumbsGenerator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end) {
            $breadcrumbs->parent('reports.index');

            $monthFormat = (string) trans('config.month_and_day');
            $startString = $start->formatLocalized($monthFormat);
            $endString   = $end->formatLocalized($monthFormat);
            $title       = (string) trans('firefly.report_default', ['start' => $startString, 'end' => $endString]);

            $breadcrumbs->push($title, route('reports.report.default', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
        }
    );

    // New user Controller
    Breadcrumbs::register(
        'new-user.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.getting_started'), route('new-user.index'));
        }
    );

    // Recurring transactions controller:
    Breadcrumbs::register(
        'recurring.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.recurrences'), route('recurring.index'));
        }
    );
    Breadcrumbs::register(
        'recurring.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Recurrence $recurrence) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push($recurrence->title, route('recurring.show', [$recurrence->id]));
        }
    );

    Breadcrumbs::register(
        'recurring.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Recurrence $recurrence) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.delete_recurring', ['title' => $recurrence->title]), route('recurring.delete', [$recurrence->id]));
        }
    );

    Breadcrumbs::register(
        'recurring.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Recurrence $recurrence) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.edit_recurrence', ['title' => $recurrence->title]), route('recurring.edit', [$recurrence->id]));
        }
    );

    Breadcrumbs::register(
        'recurring.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.create_new_recurrence'), route('recurring.create'));
        }
    );

    Breadcrumbs::register(
        'recurring.create-from-journal',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('recurring.index');
            $breadcrumbs->push(trans('firefly.create_new_recurrence'), route('recurring.create'));
        }
    );

    // Rules
    Breadcrumbs::register(
        'rules.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('firefly.rules'), route('rules.index'));
        }
    );

    Breadcrumbs::register(
        'rules.create',
        static function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup = null) {
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
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
        }
    );

    Breadcrumbs::register(
        'rules.create-from-journal',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
        }
    );

    Breadcrumbs::register(
        'rules.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Rule $rule) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.edit_rule', ['title' => $rule->title]), route('rules.edit', [$rule]));
        }
    );
    Breadcrumbs::register(
        'rules.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Rule $rule) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.delete_rule', ['title' => $rule->title]), route('rules.delete', [$rule]));
        }
    );
    Breadcrumbs::register(
        'rule-groups.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.make_new_rule_group'), route('rule-groups.create'));
        }
    );
    Breadcrumbs::register(
        'rule-groups.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.edit', [$ruleGroup]));
        }
    );
    Breadcrumbs::register(
        'rule-groups.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.delete', [$ruleGroup]));
        }
    );

    Breadcrumbs::register(
        'rules.select-transactions',
        static function (BreadcrumbsGenerator $breadcrumbs, Rule $rule) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(
                trans('firefly.rule_select_transactions', ['title' => $rule->title]),
                route('rules.select-transactions', [$rule])
            );
        }
    );

    Breadcrumbs::register(
        'rule-groups.select-transactions',
        static function (BreadcrumbsGenerator $breadcrumbs, RuleGroup $ruleGroup) {
            $breadcrumbs->parent('rules.index');
            $breadcrumbs->push(
                trans('firefly.rule_group_select_transactions', ['title' => $ruleGroup->title]),
                route('rule-groups.select-transactions', [$ruleGroup])
            );
        }
    );

    // SEARCH
    Breadcrumbs::register(
        'search.index',
        static function (BreadcrumbsGenerator $breadcrumbs, $query) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.search_result', ['query' => $query]), route('search.index'));
        }
    );

    // TAGS
    Breadcrumbs::register(
        'tags.index',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.tags'), route('tags.index'));
        }
    );

    Breadcrumbs::register(
        'tags.create',
        static function (BreadcrumbsGenerator $breadcrumbs) {
            $breadcrumbs->parent('tags.index');
            $breadcrumbs->push(trans('breadcrumbs.createTag'), route('tags.create'));
        }
    );

    Breadcrumbs::register(
        'tags.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, Tag $tag) {
            $breadcrumbs->parent('tags.show', $tag);
            $breadcrumbs->push(trans('breadcrumbs.edit_tag', ['tag' => $tag->tag]), route('tags.edit', [$tag->id]));
        }
    );

    Breadcrumbs::register(
        'tags.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, Tag $tag) {
            $breadcrumbs->parent('tags.show', $tag);
            $breadcrumbs->push(trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]), route('tags.delete', [$tag->id]));
        }
    );

    Breadcrumbs::register(
        'tags.show',
        static function (BreadcrumbsGenerator $breadcrumbs, Tag $tag, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('tags.index');

            $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id, $start, $end]));
            if (null !== $start && null !== $end) {
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string) trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string) trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('tags.show', [$tag->id, $start, $end]));
            }
        }
    );

    Breadcrumbs::register(
        'tags.show.all',
        static function (BreadcrumbsGenerator $breadcrumbs, Tag $tag) {
            $breadcrumbs->parent('tags.index');
            $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id]));
            $title = (string) trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
            $breadcrumbs->push($title, route('tags.show.all', $tag->id));
        }
    );

    // TRANSACTIONS

    Breadcrumbs::register(
        'transactions.index',
        static function (BreadcrumbsGenerator $breadcrumbs, string $what, Carbon $start = null, Carbon $end = null) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));

            if (null !== $start && null !== $end) {
                // add date range:
                $title = trans(
                    'firefly.between_dates_breadcrumb',
                    ['start' => $start->formatLocalized((string) trans('config.month_and_day')),
                     'end'   => $end->formatLocalized((string) trans('config.month_and_day')),]
                );
                $breadcrumbs->push($title, route('transactions.index', [$what, $start, $end]));
            }
        }
    );

    Breadcrumbs::register(
        'transactions.index.all',
        static function (BreadcrumbsGenerator $breadcrumbs, string $what) {
            $breadcrumbs->parent('home');
            $breadcrumbs->push(trans('breadcrumbs.' . $what . '_list'), route('transactions.index', [$what]));
        }
    );

    Breadcrumbs::register(
        'transactions.create',
        static function (BreadcrumbsGenerator $breadcrumbs, string $objectType) {
            $breadcrumbs->parent('transactions.index', $objectType);
            $breadcrumbs->push(trans('breadcrumbs.create_new_transaction'), route('transactions.create', [$objectType]));
        }
    );

    Breadcrumbs::register(
        'transactions.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionGroup $group) {
            $breadcrumbs->parent('transactions.show', $group);

            /** @var TransactionJournal $first */
            $first = $group->transactionJournals()->first();

            $breadcrumbs->push(
                trans('breadcrumbs.edit_journal', ['description' => limitStringLength($first->description)]),
                route('transactions.edit', [$group->id])
            );
        }
    );

    // also edit reconciliations:
    Breadcrumbs::register(
        'accounts.reconcile.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionJournal $journal) {
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

            $journal = $group->transactionJournals->first();
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
            if ('opening balance' === $type) {
                // TODO  link to account.
                $breadcrumbs->push($title, route('transactions.show', [$group->id]));

                return;
            }
            if ('reconciliation' === $type) {
                // TODO  link to account.
                $breadcrumbs->push($title, route('transactions.show', [$group->id]));

                return;
            }

            $breadcrumbs->parent('transactions.index', $type);
            $breadcrumbs->push($title, route('transactions.show', [$group->id]));
        }
    );

    Breadcrumbs::register(
        'transactions.convert.index',
        static function (BreadcrumbsGenerator $breadcrumbs, TransactionGroup $group, string $groupTitle) {
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
            if (!empty($journals)) {
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
            $objectType = strtolower(reset($journals)['transaction_type_type']);
            $breadcrumbs->parent('transactions.index', $objectType);
            $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.delete', ['']));
        }
    );

    // BULK EDIT
    Breadcrumbs::register(
        'transactions.bulk.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, array $journals): void {
            if (!empty($journals)) {
                $ids = Arr::pluck($journals, 'transaction_journal_id');
                $first = reset($journals);
                $breadcrumbs->parent('transactions.index', strtolower($first['transaction_type_type']));
                $breadcrumbs->push(trans('firefly.mass_bulk_journals'), route('transactions.bulk.edit', $ids));

                return;
            }

            $breadcrumbs->parent('index');
        }
    );

    // object groups
    Breadcrumbs::register(
        'object-groups.index',
        static function (BreadcrumbsGenerator $breadcrumbs): void {
            $breadcrumbs->parent('index');
            $breadcrumbs->push(trans('firefly.object_groups_breadcrumb'), route('object-groups.index'));
        }
    );

    Breadcrumbs::register(
        'object-groups.edit',
        static function (BreadcrumbsGenerator $breadcrumbs, ObjectGroup $objectGroup) {
            $breadcrumbs->parent('object-groups.index');
            $breadcrumbs->push(trans('breadcrumbs.edit_object_group', ['title' => $objectGroup->title]), route('object-groups.edit', [$objectGroup->id]));
        }
    );

    Breadcrumbs::register(
        'object-groups.delete',
        static function (BreadcrumbsGenerator $breadcrumbs, ObjectGroup $objectGroup) {
            $breadcrumbs->parent('object-groups.index');
            $breadcrumbs->push(trans('breadcrumbs.delete_object_group', ['title' => $objectGroup->title]), route('object-groups.delete', [$objectGroup->id]));
        }
    );

} catch (DuplicateBreadcrumbException $e) {
    // @ignoreException
}
