<?php

/*
 * bindables.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\InvitedUser;
use FireflyIII\Models\LinkType;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionType;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookAttempt;
use FireflyIII\Models\WebhookMessage;
use FireflyIII\Support\Binder\AccountList;
use FireflyIII\Support\Binder\BudgetList;
use FireflyIII\Support\Binder\CategoryList;
use FireflyIII\Support\Binder\CLIToken;
use FireflyIII\Support\Binder\CurrencyCode;
use FireflyIII\Support\Binder\Date;
use FireflyIII\Support\Binder\DynamicConfigKey;
use FireflyIII\Support\Binder\EitherConfigKey;
use FireflyIII\Support\Binder\JournalList;
use FireflyIII\Support\Binder\TagList;
use FireflyIII\Support\Binder\TagOrId;
use FireflyIII\Support\Binder\UserGroupAccount;
use FireflyIII\Support\Binder\UserGroupBill;
use FireflyIII\Support\Binder\UserGroupExchangeRate;
use FireflyIII\Support\Binder\UserGroupTransaction;
use FireflyIII\User;

return [
    'bindables' => [
        // models
        'account'               => Account::class,
        'attachment'            => Attachment::class,
        'availableBudget'       => AvailableBudget::class,
        'bill'                  => Bill::class,
        'budget'                => Budget::class,
        'budgetLimit'           => BudgetLimit::class,
        'category'              => Category::class,
        'linkType'              => LinkType::class,
        'transactionType'       => TransactionType::class,
        'journalLink'           => TransactionJournalLink::class,
        'currency'              => TransactionCurrency::class,
        'objectGroup'           => ObjectGroup::class,
        'piggyBank'             => PiggyBank::class,
        'preference'            => Preference::class,
        'tj'                    => TransactionJournal::class,
        'tag'                   => Tag::class,
        'recurrence'            => Recurrence::class,
        'rule'                  => Rule::class,
        'ruleGroup'             => RuleGroup::class,
        'transactionGroup'      => TransactionGroup::class,
        'user'                  => User::class,
        'webhook'               => Webhook::class,
        'webhookMessage'        => WebhookMessage::class,
        'webhookAttempt'        => WebhookAttempt::class,
        'invitedUser'           => InvitedUser::class,

        // strings
        'currency_code'         => CurrencyCode::class,

        // dates
        'start_date'            => Date::class,
        'end_date'              => Date::class,
        'date'                  => Date::class,

        // lists
        'accountList'           => AccountList::class,
        'doubleList'            => AccountList::class,
        'budgetList'            => BudgetList::class,
        'journalList'           => JournalList::class,
        'categoryList'          => CategoryList::class,
        'tagList'               => TagList::class,

        // others
        'fromCurrencyCode'      => CurrencyCode::class,
        'toCurrencyCode'        => CurrencyCode::class,
        'cliToken'              => CLIToken::class,
        'tagOrId'               => TagOrId::class,
        'dynamicConfigKey'      => DynamicConfigKey::class,
        'eitherConfigKey'       => EitherConfigKey::class,

        // V2 API endpoints:
        'userGroupAccount'      => UserGroupAccount::class,
        'userGroupTransaction'  => UserGroupTransaction::class,
        'userGroupBill'         => UserGroupBill::class,
        'userGroupExchangeRate' => UserGroupExchangeRate::class,
        'userGroup'             => UserGroup::class,
    ],
];
