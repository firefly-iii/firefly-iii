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
use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator;
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
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\Webhook;
use FireflyIII\User;
use Illuminate\Support\Arr;

if (!function_exists('limitStringLength')) {
    /**
     * Cuts away the middle of a string when it's very long.
     */
    function limitStringLength(string $string): string
    {
        $maxChars = 75;
        $length   = strlen($string);
        $result   = $string;
        if ($length > $maxChars) {
            $result = substr_replace($string, ' ... ', (int)($maxChars / 2), $length - $maxChars);
        }

        return $result;
    }
}

// HOME
Breadcrumbs::for(
    'home',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
    }
);

Breadcrumbs::for(
    'index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->push(trans('breadcrumbs.home'), route('index'));
    }
);

// ACCOUNTS
Breadcrumbs::for(
    'accounts.index',
    static function (Generator $breadcrumbs, string $what): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.'.strtolower(e($what)).'_accounts'), route('accounts.index', [$what]));
    }
);
Breadcrumbs::for( // inactive
    'accounts.inactive.index',
    static function (Generator $breadcrumbs, string $what): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.'.strtolower(e($what)).'_accounts_inactive'), route('accounts.inactive.index', [$what]));
    }
);

Breadcrumbs::for(
    'accounts.create',
    static function (Generator $breadcrumbs, string $what): void {
        $breadcrumbs->parent('accounts.index', $what);
        $breadcrumbs->push(trans('firefly.new_'.strtolower(e($what)).'_account'), route('accounts.create', [$what]));
    }
);

Breadcrumbs::for(
    'accounts.show',
    static function (Generator $breadcrumbs, Account $account, ?Carbon $start = null, ?Carbon $end = null): void {
        $what = config('firefly.shortNamesByFullName.'.$account->accountType->type);

        $breadcrumbs->parent('accounts.index', $what);
        $breadcrumbs->push(limitStringLength($account->name), route('accounts.show.all', [$account->id]));
        if (null !== $start && null !== $end) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                [
                    'start' => $start->isoFormat((string)trans('config.month_and_day_js')),
                    'end'   => $end->isoFormat((string)trans('config.month_and_day_js')),
                ]
            );
            $breadcrumbs->push($title, route('accounts.show', $account));
        }
    }
);

Breadcrumbs::for(
    'accounts.show.all',
    static function (Generator $breadcrumbs, Account $account): void {
        $what = config('firefly.shortNamesByFullName.'.$account->accountType->type);

        $breadcrumbs->parent('accounts.index', $what);
        $breadcrumbs->push(limitStringLength($account->name), route('accounts.show', [$account->id]));
    }
);

Breadcrumbs::for(
    'accounts.reconcile',
    static function (Generator $breadcrumbs, Account $account): void {
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push(trans('firefly.reconcile_account', ['account' => $account->name]), route('accounts.reconcile', [$account->id]));
    }
);

Breadcrumbs::for(
    'accounts.reconcile.show',
    static function (Generator $breadcrumbs, Account $account, TransactionJournal $journal): void {
        $breadcrumbs->parent('accounts.show', $account);
        $title = trans('firefly.reconciliation').' "'.$journal->description.'"';
        $breadcrumbs->push($title, route('accounts.reconcile.show', [$journal->id]));
    }
);

Breadcrumbs::for(
    'accounts.delete',
    static function (Generator $breadcrumbs, Account $account): void {
        $breadcrumbs->parent('accounts.show', $account);
        $breadcrumbs->push(trans('firefly.delete_account', ['name' => limitStringLength($account->name)]), route('accounts.delete', [$account->id]));
    }
);

Breadcrumbs::for(
    'accounts.edit',
    static function (Generator $breadcrumbs, Account $account): void {
        $breadcrumbs->parent('accounts.show', $account);
        $what = config('firefly.shortNamesByFullName.'.$account->accountType->type);

        $breadcrumbs->push(
            trans('firefly.edit_'.$what.'_account', ['name' => limitStringLength($account->name)]),
            route('accounts.edit', [$account->id])
        );
    }
);

// ADMIN
Breadcrumbs::for(
    'settings.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.system_settings'), route('settings.index'));
    }
);

Breadcrumbs::for(
    'settings.notification.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.administration'), route('settings.index'));
        $breadcrumbs->push(trans('breadcrumbs.notification_index'), route('settings.notification.index'));
    }
);

Breadcrumbs::for(
    'settings.users',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('settings.index');
        $breadcrumbs->push(trans('firefly.list_all_users'), route('settings.users'));
    }
);

Breadcrumbs::for(
    'settings.users.show',
    static function (Generator $breadcrumbs, User $user): void {
        $breadcrumbs->parent('settings.users');
        $breadcrumbs->push(trans('firefly.single_user_administration', ['email' => $user->email]), route('settings.users.show', [$user->id]));
    }
);
Breadcrumbs::for(
    'settings.users.edit',
    static function (Generator $breadcrumbs, User $user): void {
        $breadcrumbs->parent('settings.users');
        $breadcrumbs->push(trans('firefly.edit_user', ['email' => $user->email]), route('settings.users.edit', [$user->id]));
    }
);
Breadcrumbs::for(
    'settings.users.delete',
    static function (Generator $breadcrumbs, User $user): void {
        $breadcrumbs->parent('settings.users');
        $breadcrumbs->push(trans('firefly.delete_user', ['email' => $user->email]), route('settings.users.delete', [$user->id]));
    }
);

Breadcrumbs::for(
    'settings.users.domains',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('settings.index');
        $breadcrumbs->push(trans('firefly.blocked_domains'), route('settings.users.domains'));
    }
);

Breadcrumbs::for(
    'settings.configuration.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('settings.index');
        $breadcrumbs->push(trans('firefly.instance_configuration'), route('settings.configuration.index'));
    }
);
Breadcrumbs::for(
    'settings.update-check',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('settings.index');
        $breadcrumbs->push(trans('firefly.update_check_title'), route('settings.update-check'));
    }
);

Breadcrumbs::for(
    'settings.links.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('settings.index');
        $breadcrumbs->push(trans('firefly.journal_link_configuration'), route('settings.links.index'));
    }
);

Breadcrumbs::for(
    'settings.links.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('settings.links.index');
        $breadcrumbs->push(trans('firefly.create_new_link_type'), route('settings.links.create'));
    }
);

Breadcrumbs::for(
    'settings.links.show',
    static function (Generator $breadcrumbs, LinkType $linkType): void {
        $breadcrumbs->parent('settings.links.index');
        $breadcrumbs->push(trans('firefly.overview_for_link', ['name' => limitStringLength($linkType->name)]), route('settings.links.show', [$linkType->id]));
    }
);

Breadcrumbs::for(
    'settings.links.edit',
    static function (Generator $breadcrumbs, LinkType $linkType): void {
        $breadcrumbs->parent('settings.links.index');
        $breadcrumbs->push(trans('firefly.edit_link_type', ['name' => limitStringLength($linkType->name)]), route('settings.links.edit', [$linkType->id]));
    }
);

Breadcrumbs::for(
    'settings.links.delete',
    static function (Generator $breadcrumbs, LinkType $linkType): void {
        $breadcrumbs->parent('settings.links.index');
        $breadcrumbs->push(trans('firefly.delete_link_type', ['name' => limitStringLength($linkType->name)]), route('settings.links.delete', [$linkType->id]));
    }
);

Breadcrumbs::for(
    'transactions.link.delete',
    static function (Generator $breadcrumbs, TransactionJournalLink $link): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.delete_journal_link'), route('transactions.link.delete', $link->id));
    }
);

// ATTACHMENTS
Breadcrumbs::for(
    'attachments.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.attachments'), route('attachments.index'));
    }
);

Breadcrumbs::for(
    'attachments.edit',
    static function (Generator $breadcrumbs, Attachment $attachment): void {
        /** @var Account|Bill|TransactionJournal $object */
        $object = $attachment->attachable;
        if ($object instanceof TransactionJournal) {
            $group = $object->transactionGroup;
            if ($group instanceof TransactionGroup) {
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
Breadcrumbs::for(
    'attachments.delete',
    static function (Generator $breadcrumbs, Attachment $attachment): void {
        /** @var Account|Bill|TransactionJournal $object */
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
Breadcrumbs::for(
    'bills.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.bills'), route('bills.index'));
    }
);
Breadcrumbs::for(
    'bills.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('bills.index');
        $breadcrumbs->push(trans('breadcrumbs.newBill'), route('bills.create'));
    }
);

Breadcrumbs::for(
    'bills.edit',
    static function (Generator $breadcrumbs, Bill $bill): void {
        $breadcrumbs->parent('bills.show', $bill);
        $breadcrumbs->push(trans('breadcrumbs.edit_bill', ['name' => limitStringLength($bill->name)]), route('bills.edit', [$bill->id]));
    }
);
Breadcrumbs::for(
    'bills.delete',
    static function (Generator $breadcrumbs, Bill $bill): void {
        $breadcrumbs->parent('bills.show', $bill);
        $breadcrumbs->push(trans('breadcrumbs.delete_bill', ['name' => limitStringLength($bill->name)]), route('bills.delete', [$bill->id]));
    }
);

Breadcrumbs::for(
    'bills.show',
    static function (Generator $breadcrumbs, Bill $bill): void {
        $breadcrumbs->parent('bills.index');
        $breadcrumbs->push(limitStringLength($bill->name), route('bills.show', [$bill->id]));
    }
);

// SUBSCRIPTIONS
Breadcrumbs::for(
    'subscriptions.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.bills'), route('subscriptions.index'));
    }
);
Breadcrumbs::for(
    'subscriptions.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('subscriptions.index');
        $breadcrumbs->push(trans('breadcrumbs.newBill'), route('subscriptions.create'));
    }
);

Breadcrumbs::for(
    'subscriptions.edit',
    static function (Generator $breadcrumbs, Bill $bill): void {
        $breadcrumbs->parent('subscriptions.show', $bill);
        $breadcrumbs->push(trans('breadcrumbs.edit_bill', ['name' => limitStringLength($bill->name)]), route('subscriptions.edit', [$bill->id]));
    }
);
Breadcrumbs::for(
    'subscriptions.delete',
    static function (Generator $breadcrumbs, Bill $bill): void {
        $breadcrumbs->parent('subscriptions.show', $bill);
        $breadcrumbs->push(trans('breadcrumbs.delete_bill', ['name' => limitStringLength($bill->name)]), route('subscriptions.delete', [$bill->id]));
    }
);

Breadcrumbs::for(
    'subscriptions.show',
    static function (Generator $breadcrumbs, Bill $bill): void {
        $breadcrumbs->parent('subscriptions.index');
        $breadcrumbs->push(limitStringLength($bill->name), route('subscriptions.show', [$bill->id]));
    }
);

// BUDGETS
Breadcrumbs::for(
    'budgets.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.budgets'), route('budgets.index'));
    }
);
Breadcrumbs::for(
    'budgets.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(trans('firefly.create_new_budget'), route('budgets.create'));
    }
);

Breadcrumbs::for(
    'budgets.edit',
    static function (Generator $breadcrumbs, Budget $budget): void {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push(trans('firefly.edit_budget', ['name' => limitStringLength($budget->name)]), route('budgets.edit', [$budget->id]));
    }
);
Breadcrumbs::for(
    'budgets.delete',
    static function (Generator $breadcrumbs, Budget $budget): void {
        $breadcrumbs->parent('budgets.show', $budget);
        $breadcrumbs->push(trans('firefly.delete_budget', ['name' => limitStringLength($budget->name)]), route('budgets.delete', [$budget->id]));
    }
);

Breadcrumbs::for(
    'budgets.no-budget',
    static function (Generator $breadcrumbs, ?Carbon $start = null, ?Carbon $end = null): void {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));
        if (null !== $start && null !== $end) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                [
                    'start' => $start->isoFormat((string)trans('config.month_and_day_js')),
                    'end'   => $end->isoFormat((string)trans('config.month_and_day_js')),
                ]
            );
            $breadcrumbs->push($title, route('budgets.no-budget'));
        }
    }
);

Breadcrumbs::for(
    'budgets.no-budget-all',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(trans('firefly.journals_without_budget'), route('budgets.no-budget'));
        $breadcrumbs->push(trans('firefly.everything'), route('budgets.no-budget-all'));
    }
);

Breadcrumbs::for(
    'budgets.show',
    static function (Generator $breadcrumbs, Budget $budget): void {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(limitStringLength($budget->name), route('budgets.show', [$budget->id]));
        $breadcrumbs->push(trans('firefly.everything'), route('budgets.show', [$budget->id]));
    }
);

Breadcrumbs::for(
    'budgets.show.limit',
    static function (Generator $breadcrumbs, Budget $budget, BudgetLimit $budgetLimit): void {
        $breadcrumbs->parent('budgets.index');
        $breadcrumbs->push(limitStringLength($budget->name), route('budgets.show', [$budget->id]));

        $title = trans(
            'firefly.between_dates_breadcrumb',
            [
                'start' => $budgetLimit->start_date->isoFormat((string)trans('config.month_and_day_js')),
                'end'   => $budgetLimit->end_date->isoFormat((string)trans('config.month_and_day_js')),
            ]
        );

        $breadcrumbs->push(
            $title,
            route('budgets.show.limit', [$budget->id, $budgetLimit->id])
        );
    }
);

// CATEGORIES
Breadcrumbs::for(
    'categories.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.categories'), route('categories.index'));
    }
);
Breadcrumbs::for(
    'categories.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(trans('firefly.new_category'), route('categories.create'));
    }
);

Breadcrumbs::for(
    'categories.edit',
    static function (Generator $breadcrumbs, Category $category): void {
        $breadcrumbs->parent('categories.show.all', $category);
        $breadcrumbs->push(trans('firefly.edit_category', ['name' => limitStringLength($category->name)]), route('categories.edit', [$category->id]));
    }
);
Breadcrumbs::for(
    'categories.delete',
    static function (Generator $breadcrumbs, Category $category): void {
        $breadcrumbs->parent('categories.show', $category);
        $breadcrumbs->push(trans('firefly.delete_category', ['name' => limitStringLength($category->name)]), route('categories.delete', [$category->id]));
    }
);

Breadcrumbs::for(
    'categories.show',
    static function (Generator $breadcrumbs, Category $category, ?Carbon $start = null, ?Carbon $end = null): void {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(limitStringLength($category->name), route('categories.show', [$category->id]));
        if (null !== $start && null !== $end) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                [
                    'start' => $start->isoFormat((string)trans('config.month_and_day_js')),
                    'end'   => $end->isoFormat((string)trans('config.month_and_day_js')),
                ]
            );
            $breadcrumbs->push($title, route('categories.show', [$category->id]));
        }
    }
);

Breadcrumbs::for(
    'categories.show.all',
    static function (Generator $breadcrumbs, Category $category): void {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(limitStringLength($category->name), route('categories.show', [$category->id]));
        $breadcrumbs->push(trans('firefly.everything'), route('categories.show.all', [$category->id]));
    }
);

Breadcrumbs::for(
    'categories.no-category',
    static function (Generator $breadcrumbs, ?Carbon $start = null, ?Carbon $end = null): void {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));
        if (null !== $start && null !== $end) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                [
                    'start' => $start->isoFormat((string)trans('config.month_and_day_js')),
                    'end'   => $end->isoFormat((string)trans('config.month_and_day_js')),
                ]
            );
            $breadcrumbs->push($title, route('categories.no-category'));
        }
    }
);

Breadcrumbs::for(
    'categories.no-category.all',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('categories.index');
        $breadcrumbs->push(trans('firefly.journals_without_category'), route('categories.no-category'));
        $breadcrumbs->push(trans('firefly.everything'), route('categories.no-category.all'));
    }
);

// CURRENCIES
Breadcrumbs::for(
    'currencies.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.currencies'), route('currencies.index'));
    }
);

Breadcrumbs::for(
    'currencies.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('currencies.index');
        $breadcrumbs->push(trans('firefly.create_currency'), route('currencies.create'));
    }
);

Breadcrumbs::for(
    'currencies.edit',
    static function (Generator $breadcrumbs, TransactionCurrency $currency): void {
        $breadcrumbs->parent('currencies.index');
        $breadcrumbs->push(trans('breadcrumbs.edit_currency', ['name' => $currency->name]), route('currencies.edit', [$currency->id]));
    }
);
Breadcrumbs::for(
    'currencies.delete',
    static function (Generator $breadcrumbs, TransactionCurrency $currency): void {
        $breadcrumbs->parent('currencies.index');
        $breadcrumbs->push(trans('breadcrumbs.delete_currency', ['name' => $currency->name]), route('currencies.delete', [$currency->id]));
    }
);

// EXPORT
Breadcrumbs::for(
    'export.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.export_data_bc'), route('export.index'));
    }
);

// PIGGY BANKS
Breadcrumbs::for(
    'piggy-banks.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.piggyBanks'), route('piggy-banks.index'));
    }
);
Breadcrumbs::for(
    'piggy-banks.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('piggy-banks.index');
        $breadcrumbs->push(trans('breadcrumbs.newPiggyBank'), route('piggy-banks.create'));
    }
);

Breadcrumbs::for(
    'piggy-banks.edit',
    static function (Generator $breadcrumbs, PiggyBank $piggyBank): void {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(trans('breadcrumbs.edit_piggyBank', ['name' => $piggyBank->name]), route('piggy-banks.edit', [$piggyBank->id]));
    }
);
Breadcrumbs::for(
    'piggy-banks.delete',
    static function (Generator $breadcrumbs, PiggyBank $piggyBank): void {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]), route('piggy-banks.delete', [$piggyBank->id]));
    }
);

Breadcrumbs::for(
    'piggy-banks.show',
    static function (Generator $breadcrumbs, PiggyBank $piggyBank): void {
        $breadcrumbs->parent('piggy-banks.index');
        $breadcrumbs->push($piggyBank->name, route('piggy-banks.show', [$piggyBank->id]));
    }
);

Breadcrumbs::for(
    'piggy-banks.add-money-mobile',
    static function (Generator $breadcrumbs, PiggyBank $piggyBank): void {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(trans('firefly.add_money_to_piggy', ['name' => $piggyBank->name]), route('piggy-banks.add-money-mobile', [$piggyBank->id]));
    }
);

Breadcrumbs::for(
    'piggy-banks.remove-money-mobile',
    static function (Generator $breadcrumbs, PiggyBank $piggyBank): void {
        $breadcrumbs->parent('piggy-banks.show', $piggyBank);
        $breadcrumbs->push(
            trans('firefly.remove_money_from_piggy_title', ['name' => $piggyBank->name]),
            route('piggy-banks.remove-money-mobile', [$piggyBank->id])
        );
    }
);

// PREFERENCES
Breadcrumbs::for(
    'preferences.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.preferences'), route('preferences.index'));
    }
);



Breadcrumbs::for(
    'profile.new-backup-codes',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
    }
);

Breadcrumbs::for(
    'profile.logout-others',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.logout_others'), route('profile.logout-others'));
    }
);

// Profile MFA
Breadcrumbs::for(
    'profile.mfa.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('breadcrumbs.profile_mfa'), route('profile.mfa.index'));
    }
);
Breadcrumbs::for(
    'profile.mfa.enableMFA',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.mfa.index');
        $breadcrumbs->push(trans('breadcrumbs.mfa_enableMFA'), route('profile.mfa.enableMFA'));
    }
);

Breadcrumbs::for(
    'profile.mfa.disableMFA',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.mfa.index');
        $breadcrumbs->push(trans('breadcrumbs.mfa_disableMFA'), route('profile.mfa.disableMFA'));
    }
);

Breadcrumbs::for(
    'profile.mfa.backup-codes',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.mfa.index');
        $breadcrumbs->push(trans('breadcrumbs.mfa_backup_codes'), route('profile.mfa.backup-codes'));
    }
);
Breadcrumbs::for(
    'profile.mfa.backup-codes.post',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.mfa.index');
        $breadcrumbs->push(trans('breadcrumbs.mfa_backup_codes'), route('profile.mfa.backup-codes'));
    }
);

// exchange rates
Breadcrumbs::for(
    'exchange-rates.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.exchange_rates_index'), route('exchange-rates.index'));
    }
);

Breadcrumbs::for(
    'exchange-rates.rates',
    static function (Generator $breadcrumbs, TransactionCurrency $from, TransactionCurrency $to): void {
        $breadcrumbs->parent('exchange-rates.index');
        $breadcrumbs->push(trans('breadcrumbs.exchange_rates_rates', ['from' => $from->name, 'to' => $to->name]), route('exchange-rates.rates', [$from->code, $to->code]));
    }
);


// PROFILE
Breadcrumbs::for(
    'profile.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.profile'), route('profile.index'));
    }
);
Breadcrumbs::for(
    'profile.change-password',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('breadcrumbs.changePassword'), route('profile.change-password'));
    }
);

Breadcrumbs::for(
    'profile.change-email',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('breadcrumbs.change_email'), route('profile.change-email'));
    }
);

Breadcrumbs::for(
    'profile.delete-account',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('profile.index');
        $breadcrumbs->push(trans('firefly.delete_account'), route('profile.delete-account'));
    }
);

// REPORTS
Breadcrumbs::for(
    'reports.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.reports'), route('reports.index'));
    }
);

Breadcrumbs::for(
    'reports.report.audit',
    static function (Generator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end): void {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day_js');
        $startString = $start->isoFormat($monthFormat);
        $endString   = $end->isoFormat($monthFormat);
        $title       = (string)trans('firefly.report_audit', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.audit', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);
Breadcrumbs::for(
    'reports.report.budget',
    static function (Generator $breadcrumbs, string $accountIds, string $budgetIds, Carbon $start, Carbon $end): void {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day_js');
        $startString = $start->isoFormat($monthFormat);
        $endString   = $end->isoFormat($monthFormat);
        $title       = (string)trans('firefly.report_budget', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.budget', [$accountIds, $budgetIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::for(
    'reports.report.tag',
    static function (Generator $breadcrumbs, string $accountIds, string $tagTags, Carbon $start, Carbon $end): void {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day_js');
        $startString = $start->isoFormat($monthFormat);
        $endString   = $end->isoFormat($monthFormat);
        $title       = (string)trans('firefly.report_tag', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.tag', [$accountIds, $tagTags, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::for(
    'reports.report.category',
    static function (Generator $breadcrumbs, string $accountIds, string $categoryIds, Carbon $start, Carbon $end): void {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day_js');
        $startString = $start->isoFormat($monthFormat);
        $endString   = $end->isoFormat($monthFormat);
        $title       = (string)trans('firefly.report_category', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.category', [$accountIds, $categoryIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::for(
    'reports.report.double',
    static function (Generator $breadcrumbs, string $accountIds, string $doubleIds, Carbon $start, Carbon $end): void {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day_js');
        $startString = $start->isoFormat($monthFormat);
        $endString   = $end->isoFormat($monthFormat);
        $title       = (string)trans('firefly.report_double', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.double', [$accountIds, $doubleIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

Breadcrumbs::for(
    'reports.report.default',
    static function (Generator $breadcrumbs, string $accountIds, Carbon $start, Carbon $end): void {
        $breadcrumbs->parent('reports.index');

        $monthFormat = (string)trans('config.month_and_day_js');
        $startString = $start->isoFormat($monthFormat);
        $endString   = $end->isoFormat($monthFormat);
        $title       = (string)trans('firefly.report_default', ['start' => $startString, 'end' => $endString]);

        $breadcrumbs->push($title, route('reports.report.default', [$accountIds, $start->format('Ymd'), $end->format('Ymd')]));
    }
);

// New user Controller
Breadcrumbs::for(
    'new-user.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.getting_started'), route('new-user.index'));
    }
);

// Recurring transactions controller:
Breadcrumbs::for(
    'recurring.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.recurrences'), route('recurring.index'));
    }
);
Breadcrumbs::for(
    'recurring.show',
    static function (Generator $breadcrumbs, Recurrence $recurrence): void {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push($recurrence->title, route('recurring.show', [$recurrence->id]));
    }
);

Breadcrumbs::for(
    'recurring.delete',
    static function (Generator $breadcrumbs, Recurrence $recurrence): void {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push(trans('firefly.delete_recurring', ['title' => $recurrence->title]), route('recurring.delete', [$recurrence->id]));
    }
);

Breadcrumbs::for(
    'recurring.edit',
    static function (Generator $breadcrumbs, Recurrence $recurrence): void {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push(trans('firefly.edit_recurrence', ['title' => $recurrence->title]), route('recurring.edit', [$recurrence->id]));
    }
);

Breadcrumbs::for(
    'recurring.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push(trans('firefly.create_new_recurrence'), route('recurring.create'));
    }
);

Breadcrumbs::for(
    'recurring.create-from-journal',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('recurring.index');
        $breadcrumbs->push(trans('firefly.create_new_recurrence'), route('recurring.create'));
    }
);

// Rules
Breadcrumbs::for(
    'rules.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('firefly.rules'), route('rules.index'));
    }
);

Breadcrumbs::for(
    'rules.create',
    static function (Generator $breadcrumbs, ?RuleGroup $ruleGroup = null): void {
        $breadcrumbs->parent('rules.index');
        if (null === $ruleGroup) {
            $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
        }
        if (null !== $ruleGroup) {
            $breadcrumbs->push(trans('firefly.make_new_rule', ['title' => $ruleGroup->title]), route('rules.create', [$ruleGroup]));
        }
    }
);

Breadcrumbs::for(
    'rules.create-from-bill',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
    }
);

Breadcrumbs::for(
    'rules.create-from-journal',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.make_new_rule_no_group'), route('rules.create'));
    }
);

Breadcrumbs::for(
    'rules.edit',
    static function (Generator $breadcrumbs, Rule $rule): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.edit_rule', ['nr' => $rule->order, 'title' => $rule->title]), route('rules.edit', [$rule]));
    }
);
Breadcrumbs::for(
    'rules.delete',
    static function (Generator $breadcrumbs, Rule $rule): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.delete_rule', ['title' => $rule->title]), route('rules.delete', [$rule]));
    }
);
Breadcrumbs::for(
    'rule-groups.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.make_new_rule_group'), route('rule-groups.create'));
    }
);
Breadcrumbs::for(
    'rule-groups.edit',
    static function (Generator $breadcrumbs, RuleGroup $ruleGroup): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.edit', [$ruleGroup]));
    }
);
Breadcrumbs::for(
    'rule-groups.delete',
    static function (Generator $breadcrumbs, RuleGroup $ruleGroup): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]), route('rule-groups.delete', [$ruleGroup]));
    }
);

Breadcrumbs::for(
    'rules.select-transactions',
    static function (Generator $breadcrumbs, Rule $rule): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(
            trans('firefly.rule_select_transactions', ['title' => $rule->title]),
            route('rules.select-transactions', [$rule])
        );
    }
);

Breadcrumbs::for(
    'rule-groups.select-transactions',
    static function (Generator $breadcrumbs, RuleGroup $ruleGroup): void {
        $breadcrumbs->parent('rules.index');
        $breadcrumbs->push(
            trans('firefly.rule_group_select_transactions', ['title' => $ruleGroup->title]),
            route('rule-groups.select-transactions', [$ruleGroup])
        );
    }
);

// SEARCH
Breadcrumbs::for(
    'search.index',
    static function (Generator $breadcrumbs, $query): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.search_result', ['query' => $query]), route('search.index'));
    }
);

// TAGS
Breadcrumbs::for(
    'tags.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.tags'), route('tags.index'));
    }
);

Breadcrumbs::for(
    'tags.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('tags.index');
        $breadcrumbs->push(trans('breadcrumbs.createTag'), route('tags.create'));
    }
);

Breadcrumbs::for(
    'tags.edit',
    static function (Generator $breadcrumbs, Tag $tag): void {
        $breadcrumbs->parent('tags.show', $tag);
        $breadcrumbs->push(trans('breadcrumbs.edit_tag', ['tag' => $tag->tag]), route('tags.edit', [$tag->id]));
    }
);

Breadcrumbs::for(
    'tags.delete',
    static function (Generator $breadcrumbs, Tag $tag): void {
        $breadcrumbs->parent('tags.show', $tag);
        $breadcrumbs->push(trans('breadcrumbs.delete_tag', ['tag' => $tag->tag]), route('tags.delete', [$tag->id]));
    }
);

Breadcrumbs::for(
    'tags.show',
    static function (Generator $breadcrumbs, Tag $tag, ?Carbon $start = null, ?Carbon $end = null): void {
        $breadcrumbs->parent('tags.index');

        $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id, $start, $end]));
        if (null !== $start && null !== $end) {
            $title = trans(
                'firefly.between_dates_breadcrumb',
                [
                    'start' => $start->isoFormat((string)trans('config.month_and_day_js')),
                    'end'   => $end->isoFormat((string)trans('config.month_and_day_js')),
                ]
            );
            $breadcrumbs->push($title, route('tags.show', [$tag->id, $start, $end]));
        }
    }
);

Breadcrumbs::for(
    'tags.show.all',
    static function (Generator $breadcrumbs, Tag $tag): void {
        $breadcrumbs->parent('tags.index');
        $breadcrumbs->push($tag->tag, route('tags.show', [$tag->id]));
        $title = (string)trans('firefly.all_journals_for_tag', ['tag' => $tag->tag]);
        $breadcrumbs->push($title, route('tags.show.all', $tag->id));
    }
);

// TRANSACTIONS

Breadcrumbs::for(
    'transactions.index',
    static function (Generator $breadcrumbs, string $what, ?Carbon $start = null, ?Carbon $end = null): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.'.$what.'_list'), route('transactions.index', [$what]));

        if (null !== $start && null !== $end) {
            // add date range:
            $title = trans(
                'firefly.between_dates_breadcrumb',
                [
                    'start' => $start->isoFormat((string)trans('config.month_and_day_js')),
                    'end'   => $end->isoFormat((string)trans('config.month_and_day_js')),
                ]
            );
            $breadcrumbs->push($title, route('transactions.index', [$what, $start, $end]));
        }
    }
);

Breadcrumbs::for(
    'transactions.index.all',
    static function (Generator $breadcrumbs, string $what): void {
        $breadcrumbs->parent('home');
        $breadcrumbs->push(trans('breadcrumbs.'.$what.'_list'), route('transactions.index', [$what]));
    }
);

Breadcrumbs::for(
    'transactions.create',
    static function (Generator $breadcrumbs, string $objectType): void {
        $breadcrumbs->parent('transactions.index', $objectType);
        $breadcrumbs->push(trans(sprintf('breadcrumbs.create_%s', strtolower($objectType))), route('transactions.create', [$objectType]));
    }
);

Breadcrumbs::for(
    'transactions.edit',
    static function (Generator $breadcrumbs, TransactionGroup $group): void {
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
Breadcrumbs::for(
    'accounts.reconcile.edit',
    static function (Generator $breadcrumbs, TransactionJournal $journal): void {
        $breadcrumbs->parent('transactions.show', $journal);
        $breadcrumbs->push(
            trans('breadcrumbs.edit_reconciliation', ['description' => limitStringLength($journal->description)]),
            route('accounts.reconcile.edit', [$journal->id])
        );
    }
);

Breadcrumbs::for(
    'transactions.delete',
    static function (Generator $breadcrumbs, TransactionGroup $group): void {
        $breadcrumbs->parent('transactions.show', $group);

        $journal = $group->transactionJournals->first();
        $breadcrumbs->push(
            trans('breadcrumbs.delete_group', ['description' => limitStringLength($group->title ?? $journal->description)]),
            route('transactions.delete', [$group->id])
        );
    }
);

Breadcrumbs::for(
    'transactions.show',
    static function (Generator $breadcrumbs, TransactionGroup $group): void {
        /** @var TransactionJournal $first */
        $first = $group->transactionJournals()->first();
        $type  = strtolower($first->transactionType->type);
        $title = limitStringLength($first->description);
        if ($group->transactionJournals()->count() > 1) {
            $title = limitStringLength((string)$group->title);
        }
        if ('opening balance' === $type) {
            // TODO link to account
            $breadcrumbs->push($title, route('transactions.show', [$group->id]));

            return;
        }
        if ('reconciliation' === $type) {
            // TODO link to account
            $breadcrumbs->push($title, route('transactions.show', [$group->id]));

            return;
        }

        $breadcrumbs->parent('transactions.index', $type);
        $breadcrumbs->push($title, route('transactions.show', [$group->id]));
    }
);

Breadcrumbs::for(
    'transactions.convert.index',
    static function (Generator $breadcrumbs, TransactionGroup $group, string $groupTitle): void {
        $breadcrumbs->parent('transactions.show', $group);
        $breadcrumbs->push(
            trans('firefly.breadcrumb_convert_group', ['description' => limitStringLength($groupTitle)]),
            route('transactions.convert.index', [$group->id, 'something'])
        );
    }
);

// MASS TRANSACTION EDIT / DELETE
Breadcrumbs::for(
    'transactions.mass.edit',
    static function (Generator $breadcrumbs, array $journals): void {
        if (0 !== count($journals)) {
            $objectType = strtolower(reset($journals)['transaction_type_type']);
            $breadcrumbs->parent('transactions.index', $objectType);
            $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.edit', ['']));

            return;
        }
        $breadcrumbs->parent('index');
    }
);

Breadcrumbs::for(
    'transactions.mass.delete',
    static function (Generator $breadcrumbs, array $journals): void {
        $objectType = strtolower(reset($journals)['transaction_type_type']);
        $breadcrumbs->parent('transactions.index', $objectType);
        $breadcrumbs->push(trans('firefly.mass_edit_journals'), route('transactions.mass.delete', ['']));
    }
);

// BULK EDIT
Breadcrumbs::for(
    'transactions.bulk.edit',
    static function (Generator $breadcrumbs, array $journals): void {
        if (0 !== count($journals)) {
            $ids   = Arr::pluck($journals, 'transaction_journal_id');
            $first = reset($journals);
            $breadcrumbs->parent('transactions.index', strtolower($first['transaction_type_type']));
            $breadcrumbs->push(trans('firefly.mass_bulk_journals'), route('transactions.bulk.edit', $ids));

            return;
        }

        $breadcrumbs->parent('index');
    }
);

// object groups
Breadcrumbs::for(
    'object-groups.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('index');
        $breadcrumbs->push(trans('firefly.object_groups_breadcrumb'), route('object-groups.index'));
    }
);

Breadcrumbs::for(
    'object-groups.edit',
    static function (Generator $breadcrumbs, ObjectGroup $objectGroup): void {
        $breadcrumbs->parent('object-groups.index');
        $breadcrumbs->push(trans('breadcrumbs.edit_object_group', ['title' => $objectGroup->title]), route('object-groups.edit', [$objectGroup->id]));
    }
);

Breadcrumbs::for(
    'object-groups.delete',
    static function (Generator $breadcrumbs, ObjectGroup $objectGroup): void {
        $breadcrumbs->parent('object-groups.index');
        $breadcrumbs->push(trans('breadcrumbs.delete_object_group', ['title' => $objectGroup->title]), route('object-groups.delete', [$objectGroup->id]));
    }
);

// webhooks
Breadcrumbs::for(
    'webhooks.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('index');
        $breadcrumbs->push(trans('firefly.webhooks_breadcrumb'), route('webhooks.index'));
    }
);
Breadcrumbs::for(
    'webhooks.create',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('webhooks.index');
        $breadcrumbs->push(trans('firefly.webhooks_create_breadcrumb'), route('webhooks.create'));
    }
);

Breadcrumbs::for(
    'webhooks.show',
    static function (Generator $breadcrumbs, Webhook $webhook): void {
        $breadcrumbs->parent('webhooks.index');
        $breadcrumbs->push(limitStringLength($webhook->title), route('webhooks.show', [$webhook->id]));
    }
);

Breadcrumbs::for(
    'webhooks.delete',
    static function (Generator $breadcrumbs, Webhook $webhook): void {
        $breadcrumbs->parent('webhooks.show', $webhook);
        $breadcrumbs->push(trans('firefly.delete_webhook', ['title' => limitStringLength($webhook->title)]), route('webhooks.delete', [$webhook->id]));
    }
);

Breadcrumbs::for(
    'webhooks.edit',
    static function (Generator $breadcrumbs, Webhook $webhook): void {
        $breadcrumbs->parent('webhooks.show', $webhook);
        $breadcrumbs->push(trans('firefly.edit_webhook', ['title' => limitStringLength($webhook->title)]), route('webhooks.edit', [$webhook->id]));
    }
);

Breadcrumbs::for(
    'administrations.index',
    static function (Generator $breadcrumbs): void {
        $breadcrumbs->parent('index');
        $breadcrumbs->push(trans('firefly.administrations_breadcrumb'), route('administrations.index'));
    }
);

// Breadcrumbs::for(
//    'administrations.show',
//    static function (Generator $breadcrumbs, UserGroup $userGroup): void {
//        $breadcrumbs->parent('administrations.index');
//        $breadcrumbs->push(limitStringLength($userGroup->title), route('administrations.show', [$userGroup->id]));
//    }
// );

// Breadcrumbs::for(
//    'administrations.create',
//    static function (Generator $breadcrumbs): void {
//        $breadcrumbs->parent('administrations.index');
//        $breadcrumbs->push(trans('firefly.administrations_create_breadcrumb'), route('administrations.create'));
//    }
// );
Breadcrumbs::for(
    'administrations.edit',
    static function (Generator $breadcrumbs, UserGroup $userGroup): void {
        $breadcrumbs->parent('administrations.index');
        $breadcrumbs->push(trans('firefly.edit_administration_breadcrumb', ['title' => limitStringLength($userGroup->title)]), route('administrations.edit', [$userGroup->id]));
    }
);
