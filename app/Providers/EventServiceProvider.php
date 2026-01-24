<?php

/**
 * EventServiceProvider.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Providers;

use FireflyIII\Events\Admin\InvitationCreated;
use FireflyIII\Events\DestroyedTransactionGroup;
use FireflyIII\Events\Model\TransactionGroup\TriggeredStoredTransactionGroup;
use FireflyIII\Events\Preferences\UserGroupChangedPrimaryCurrency;
use FireflyIII\Events\StoredAccount;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Events\UpdatedAccount;
use FireflyIII\Events\UpdatedTransactionGroup;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Passport\Events\AccessTokenCreated;
use Override;

/**
 * Class EventServiceProvider.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class                           => [
            'FireflyIII\Handlers\Events\UserEventHandler@checkSingleUserIsAdmin',
            'FireflyIII\Handlers\Events\UserEventHandler@demoUserBackToEnglish',
        ],
        // is a User related event.
        InvitationCreated::class               => [
            'FireflyIII\Handlers\Events\AdminEventHandler@sendInvitationNotification',
            'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationInvite',
        ],

        // is a Transaction Journal related event.
        StoredTransactionGroup::class          => ['FireflyIII\Handlers\Events\StoredGroupEventHandler@runAllHandlers'],
        TriggeredStoredTransactionGroup::class => ['FireflyIII\Handlers\Events\StoredGroupEventHandler@triggerRulesManually'],
        // is a Transaction Journal related event.
        UpdatedTransactionGroup::class         => ['FireflyIII\Handlers\Events\UpdatedGroupEventHandler@runAllHandlers'],
        DestroyedTransactionGroup::class       => ['FireflyIII\Handlers\Events\DestroyedGroupEventHandler@runAllHandlers'],
        // API related events:
        AccessTokenCreated::class              => ['FireflyIII\Handlers\Events\APIEventHandler@accessTokenCreated'],

        // account related events:
        StoredAccount::class                   => ['FireflyIII\Handlers\Events\StoredAccountEventHandler@recalculateCredit'],
        UpdatedAccount::class                  => ['FireflyIII\Handlers\Events\UpdatedAccountEventHandler@recalculateCredit'],

        // preferences
        UserGroupChangedPrimaryCurrency::class => ['FireflyIII\Handlers\Events\PreferencesEventHandler@resetPrimaryCurrencyAmounts'],
    ];

    /**
     * Register any events for your application.
     */
    #[Override]
    public function boot(): void {}
}
