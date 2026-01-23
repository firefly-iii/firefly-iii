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
use FireflyIII\Events\NewVersionAvailable;
use FireflyIII\Events\Preferences\UserGroupChangedPrimaryCurrency;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\RequestedReportOnJournals;
use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Events\StoredAccount;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Events\TriggeredAuditLog;
use FireflyIII\Events\UpdatedAccount;
use FireflyIII\Events\UpdatedTransactionGroup;
use FireflyIII\Events\UserChangedEmail;
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
            //            UserAttemptedLogin::class              => [
            //                'FireflyIII\Handlers\Events\UserEventHandler@sendLoginAttemptNotification',
            //            ],
            // is a User related event.
            Login::class                           => [
                'FireflyIII\Handlers\Events\UserEventHandler@checkSingleUserIsAdmin',
                'FireflyIII\Handlers\Events\UserEventHandler@demoUserBackToEnglish',
            ],
            //            DetectedNewIPAddress::class            => [
            //                'FireflyIII\Handlers\Events\UserEventHandler@notifyNewIPAddress',
            //            ],
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
            //            UserTestsNotificationChannel::class => [
            //                'FireflyIII\Handlers\Events\UserEventHandler@sendTestNotification',
            //            ],
            // is a User related event.
            UserChangedEmail::class                => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeConfirmMail',
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeUndoMail',
            ],
            // admin related
            //            OwnerTestsNotificationChannel::class => [
            //                'FireflyIII\Handlers\Events\AdminEventHandler@sendTestNotification',
            //            ],
            NewVersionAvailable::class             => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendNewVersion',
            ],
            InvitationCreated::class               => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendInvitationNotification',
                'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationInvite',
            ],
            //            UnknownUserAttemptedLogin::class       => [
            //                'FireflyIII\Handlers\Events\AdminEventHandler@sendLoginAttemptNotification',
            //            ],

            // is a Transaction Journal related event.
            StoredTransactionGroup::class          => [
                'FireflyIII\Handlers\Events\StoredGroupEventHandler@runAllHandlers',
            ],
            TriggeredStoredTransactionGroup::class => [
                'FireflyIII\Handlers\Events\StoredGroupEventHandler@triggerRulesManually',
            ],
            // is a Transaction Journal related event.
            UpdatedTransactionGroup::class         => [
                'FireflyIII\Handlers\Events\UpdatedGroupEventHandler@runAllHandlers',
            ],
            DestroyedTransactionGroup::class       => [
                'FireflyIII\Handlers\Events\DestroyedGroupEventHandler@runAllHandlers',
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

            // subscription related events:
            //            SubscriptionNeedsExtensionOrRenewal::class                 => [
            //                'FireflyIII\Handlers\Events\BillEventHandler@warnAboutBill',
            //            ],
            //            WarnUserAboutOverdueSubscriptions::class => [
            //                'FireflyIII\Handlers\Events\BillEventHandler@warnAboutOverdueSubscriptions',
            //            ],

            // audit log events:
            TriggeredAuditLog::class               => [
                'FireflyIII\Handlers\Events\AuditEventHandler@storeAuditEvent',
            ],
            // piggy bank related events:
            //            PiggyBankAmountIsChanged::class => [
            //                'FireflyIII\Handlers\Events\Model\PiggyBankEventHandler@changePiggyAmount',
            //            ],
            //            ChangedName::class                     => [
            //                'FireflyIII\Handlers\Events\Model\PiggyBankEventHandler@changedPiggyBankName',
            //            ],

            // rule actions
            //            RuleActionFailedOnArray::class         => [
            //                'FireflyIII\Handlers\Events\Model\RuleHandler@ruleActionFailedOnArray',
            //            ],
            //            RuleActionFailedOnObject::class        => [
            //                'FireflyIII\Handlers\Events\Model\RuleHandler@ruleActionFailedOnObject',
            //            ],

            // security related
            //            EnabledMFA::class                      => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendMFAEnabledMail',
            //            ],
            //            DisabledMFA::class                     => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendMFADisabledMail',
            //            ],
            //            MFANewBackupCodes::class               => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendNewMFABackupCodesMail',
            //            ],
            //            MFAUsedBackupCode::class               => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendUsedBackupCodeMail',
            //            ],
            //            MFABackupFewLeft::class                => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendBackupFewLeftMail',
            //            ],
            //            MFABackupNoLeft::class                 => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendBackupNoLeftMail',
            //            ],
            //            MFAManyFailedAttempts::class           => [
            //                'FireflyIII\Handlers\Events\Security\MFAHandler@sendMFAFailedAttemptsMail',
            //            ],
            // preferences
            UserGroupChangedPrimaryCurrency::class => [
                'FireflyIII\Handlers\Events\PreferencesEventHandler@resetPrimaryCurrencyAmounts',
            ],
        ];

    /**
     * Register any events for your application.
     */
    #[Override]
    public function boot(): void {}
}
