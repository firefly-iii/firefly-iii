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

use FireflyIII\Events\ActuallyLoggedIn;
use FireflyIII\Events\Admin\InvitationCreated;
use FireflyIII\Events\DestroyedTransactionGroup;
use FireflyIII\Events\DetectedNewIPAddress;
use FireflyIII\Events\Model\BudgetLimit\Created;
use FireflyIII\Events\Model\BudgetLimit\Deleted;
use FireflyIII\Events\Model\BudgetLimit\Updated;
use FireflyIII\Events\Model\PiggyBank\ChangedAmount;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnArray;
use FireflyIII\Events\Model\Rule\RuleActionFailedOnObject;
use FireflyIII\Events\NewVersionAvailable;
use FireflyIII\Events\Preferences\UserGroupChangedDefaultCurrency;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Events\Security\DisabledMFA;
use FireflyIII\Events\Security\EnabledMFA;
use FireflyIII\Events\Security\MFABackupFewLeft;
use FireflyIII\Events\Security\MFABackupNoLeft;
use FireflyIII\Events\Security\MFAManyFailedAttempts;
use FireflyIII\Events\Security\MFANewBackupCodes;
use FireflyIII\Events\Security\MFAUsedBackupCode;
use FireflyIII\Events\Security\UnknownUserAttemptedLogin;
use FireflyIII\Events\Security\UserAttemptedLogin;
use FireflyIII\Events\StoredAccount;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Events\Test\OwnerTestNotificationChannel;
use FireflyIII\Events\Test\UserTestNotificationChannel;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Events\UpdatedAccount;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Events\WarnUserAboutBill;
use FireflyIII\Handlers\Observer\AccountObserver;
use FireflyIII\Handlers\Observer\AttachmentObserver;
use FireflyIII\Handlers\Observer\AutoBudgetObserver;
use FireflyIII\Handlers\Observer\AvailableBudgetObserver;
use FireflyIII\Handlers\Observer\BillObserver;
use FireflyIII\Handlers\Observer\BudgetLimitObserver;
use FireflyIII\Handlers\Observer\BudgetObserver;
use FireflyIII\Handlers\Observer\CategoryObserver;
use FireflyIII\Handlers\Observer\PiggyBankEventObserver;
use FireflyIII\Handlers\Observer\PiggyBankObserver;
use FireflyIII\Handlers\Observer\RecurrenceObserver;
use FireflyIII\Handlers\Observer\RecurrenceTransactionObserver;
use FireflyIII\Handlers\Observer\RuleGroupObserver;
use FireflyIII\Handlers\Observer\RuleObserver;
use FireflyIII\Handlers\Observer\TagObserver;
use FireflyIII\Handlers\Observer\TransactionGroupObserver;
use FireflyIII\Handlers\Observer\TransactionJournalObserver;
use FireflyIII\Handlers\Observer\TransactionObserver;
use FireflyIII\Handlers\Observer\WebhookMessageObserver;
use FireflyIII\Handlers\Observer\WebhookObserver;
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\AutoBudget;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Category;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\Webhook;
use FireflyIII\Models\WebhookMessage;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Passport\Events\AccessTokenCreated;

/**
 * Class EventServiceProvider.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen
        = [
            // is a User related event.
            RegisteredUser::class                  => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationMail',
                'FireflyIII\Handlers\Events\UserEventHandler@sendAdminRegistrationNotification',
                'FireflyIII\Handlers\Events\UserEventHandler@attachUserRole',
                'FireflyIII\Handlers\Events\UserEventHandler@createGroupMembership',
                'FireflyIII\Handlers\Events\UserEventHandler@createExchangeRates',
            ],
            UserAttemptedLogin::class              => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendLoginAttemptNotification',
            ],
            // is a User related event.
            Login::class                           => [
                'FireflyIII\Handlers\Events\UserEventHandler@checkSingleUserIsAdmin',
                'FireflyIII\Handlers\Events\UserEventHandler@demoUserBackToEnglish',
            ],
            ActuallyLoggedIn::class                => [
                'FireflyIII\Handlers\Events\UserEventHandler@storeUserIPAddress',
            ],
            DetectedNewIPAddress::class            => [
                'FireflyIII\Handlers\Events\UserEventHandler@notifyNewIPAddress',
            ],
            RequestedVersionCheckStatus::class     => [
                'FireflyIII\Handlers\Events\VersionCheckEventHandler@checkForUpdates',
            ],
            RequestedReportOnJournals::class       => [
                'FireflyIII\Handlers\Events\AutomationHandler@reportJournals',
            ],

            // is a User related event.
            RequestedNewPassword::class            => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendNewPassword',
            ],
            UserTestNotificationChannel::class     => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendTestNotification',
            ],
            // is a User related event.
            UserChangedEmail::class                => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeConfirmMail',
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeUndoMail',
            ],
            // admin related
            OwnerTestNotificationChannel::class    => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendTestNotification',
            ],
            NewVersionAvailable::class             => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendNewVersion',
            ],
            InvitationCreated::class               => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendInvitationNotification',
                'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationInvite',
            ],
            UnknownUserAttemptedLogin::class       => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendLoginAttemptNotification',
            ],

            // is a Transaction Journal related event.
            StoredTransactionGroup::class          => [
                'FireflyIII\Handlers\Events\StoredGroupEventHandler@processRules',
                'FireflyIII\Handlers\Events\StoredGroupEventHandler@recalculateCredit',
                'FireflyIII\Handlers\Events\StoredGroupEventHandler@triggerWebhooks',
            ],
            // is a Transaction Journal related event.
            UpdatedTransactionGroup::class         => [
                'FireflyIII\Handlers\Events\UpdatedGroupEventHandler@unifyAccounts',
                'FireflyIII\Handlers\Events\UpdatedGroupEventHandler@processRules',
                'FireflyIII\Handlers\Events\UpdatedGroupEventHandler@recalculateCredit',
                'FireflyIII\Handlers\Events\UpdatedGroupEventHandler@triggerWebhooks',
            ],
            DestroyedTransactionGroup::class       => [
                'FireflyIII\Handlers\Events\DestroyedGroupEventHandler@triggerWebhooks',
            ],
            // API related events:
            AccessTokenCreated::class              => [
                'FireflyIII\Handlers\Events\APIEventHandler@accessTokenCreated',
            ],

            // Webhook related event:
            RequestedSendWebhookMessages::class    => [
                'FireflyIII\Handlers\Events\WebhookEventHandler@sendWebhookMessages',
            ],

            // account related events:
            StoredAccount::class                   => [
                'FireflyIII\Handlers\Events\StoredAccountEventHandler@recalculateCredit',
            ],
            UpdatedAccount::class                  => [
                'FireflyIII\Handlers\Events\UpdatedAccountEventHandler@recalculateCredit',
            ],

            // bill related events:
            WarnUserAboutBill::class               => [
                'FireflyIII\Handlers\Events\BillEventHandler@warnAboutBill',
            ],

            // audit log events:
            TriggeredAuditLog::class               => [
                'FireflyIII\Handlers\Events\AuditEventHandler@storeAuditEvent',
            ],
            // piggy bank related events:
            ChangedAmount::class                   => [
                'FireflyIII\Handlers\Events\Model\PiggyBankEventHandler@changePiggyAmount',
            ],

            // budget related events: CRUD budget limit
            Created::class                         => [
                'FireflyIII\Handlers\Events\Model\BudgetLimitHandler@created',
            ],
            Updated::class                         => [
                'FireflyIII\Handlers\Events\Model\BudgetLimitHandler@updated',
            ],
            Deleted::class                         => [
                'FireflyIII\Handlers\Events\Model\BudgetLimitHandler@deleted',
            ],

            // rule actions
            RuleActionFailedOnArray::class         => [
                'FireflyIII\Handlers\Events\Model\RuleHandler@ruleActionFailedOnArray',
            ],
            RuleActionFailedOnObject::class        => [
                'FireflyIII\Handlers\Events\Model\RuleHandler@ruleActionFailedOnObject',
            ],

            // security related
            EnabledMFA::class                      => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendMFAEnabledMail',
            ],
            DisabledMFA::class                     => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendMFADisabledMail',
            ],
            MFANewBackupCodes::class               => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendNewMFABackupCodesMail',
            ],
            MFAUsedBackupCode::class               => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendUsedBackupCodeMail',
            ],
            MFABackupFewLeft::class                => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendBackupFewLeftMail',
            ],
            MFABackupNoLeft::class                 => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendBackupNoLeftMail',
            ],
            MFAManyFailedAttempts::class           => [
                'FireflyIII\Handlers\Events\Security\MFAHandler@sendMFAFailedAttemptsMail',
            ],
            // preferences
            UserGroupChangedDefaultCurrency::class => [
                'FireflyIII\Handlers\Events\PreferencesEventHandler@resetNativeAmounts',
            ],
        ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        $this->registerObservers();
    }

    private function registerObservers(): void
    {
        Attachment::observe(new AttachmentObserver());
        Account::observe(new AccountObserver());
        AutoBudget::observe(new AutoBudgetObserver());
        AvailableBudget::observe(new AvailableBudgetObserver());
        Bill::observe(new BillObserver());
        Budget::observe(new BudgetObserver());
        BudgetLimit::observe(new BudgetLimitObserver());
        Category::observe(new CategoryObserver());
        PiggyBank::observe(new PiggyBankObserver());
        PiggyBankEvent::observe(new PiggyBankEventObserver());
        Recurrence::observe(new RecurrenceObserver());
        RecurrenceTransaction::observe(new RecurrenceTransactionObserver());
        Rule::observe(new RuleObserver());
        RuleGroup::observe(new RuleGroupObserver());
        Tag::observe(new TagObserver());
        Transaction::observe(new TransactionObserver());
        TransactionJournal::observe(new TransactionJournalObserver());
        TransactionGroup::observe(new TransactionGroupObserver());
        Webhook::observe(new WebhookObserver());
        WebhookMessage::observe(new WebhookMessageObserver());
    }
}
