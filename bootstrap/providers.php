<?php
/*
 * providers.php
 * Copyright (c) 2026 james@firefly-iii.org
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

use FireflyIII\Providers\AccountServiceProvider;
use FireflyIII\Providers\AdminServiceProvider;
use FireflyIII\Providers\AppServiceProvider;
use FireflyIII\Providers\AttachmentServiceProvider;
use FireflyIII\Providers\BillServiceProvider;
use FireflyIII\Providers\BudgetServiceProvider;
use FireflyIII\Providers\CategoryServiceProvider;
use FireflyIII\Providers\CurrencyServiceProvider;
use FireflyIII\Providers\FireflyServiceProvider;
use FireflyIII\Providers\JournalServiceProvider;
use FireflyIII\Providers\PiggyBankServiceProvider;
use FireflyIII\Providers\RecurringServiceProvider;
use FireflyIII\Providers\RouteServiceProvider;
use FireflyIII\Providers\RuleGroupServiceProvider;
use FireflyIII\Providers\RuleServiceProvider;
use FireflyIII\Providers\SearchServiceProvider;
use FireflyIII\Providers\TagServiceProvider;
use TwigBridge\ServiceProvider;

return [
    // Package Service Providers...

    // Application Service Providers...
    AppServiceProvider::class,
    FireflyIII\Providers\AuthServiceProvider::class,
    // FireflyIII\Providers\BroadcastServiceProvider::class,
    // EventServiceProvider::class,
    RouteServiceProvider::class,

    // own stuff:
    PragmaRX\Google2FALaravel\ServiceProvider::class,
    ServiceProvider::class,

    // More service providers.
    AccountServiceProvider::class,
    AttachmentServiceProvider::class,
    BillServiceProvider::class,
    BudgetServiceProvider::class,
    CategoryServiceProvider::class,
    CurrencyServiceProvider::class,
    FireflyServiceProvider::class,
    JournalServiceProvider::class,
    PiggyBankServiceProvider::class,
    RuleServiceProvider::class,
    RuleGroupServiceProvider::class,
    SearchServiceProvider::class,
    TagServiceProvider::class,
    AdminServiceProvider::class,
    RecurringServiceProvider::class,
];
