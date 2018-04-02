<?php
/**
 * EventServiceProvider.php
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

namespace FireflyIII\Providers;

use FireflyIII\Events\AdminRequestedTestMessage;
use FireflyIII\Events\RegisteredUser;
use FireflyIII\Events\RequestedNewPassword;
use FireflyIII\Events\RequestedVersionCheckStatus;
use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Events\UserChangedEmail;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class EventServiceProvider.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * @codeCoverageIgnore
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen
        = [
            // is a User related event.
            RegisteredUser::class              => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationMail',
                'FireflyIII\Handlers\Events\UserEventHandler@attachUserRole',
            ],
            // is a User related event.
            Login::class                       => [
                'FireflyIII\Handlers\Events\UserEventHandler@checkSingleUserIsAdmin',

            ],
            RequestedVersionCheckStatus::class => [
                'FireflyIII\Handlers\Events\VersionCheckEventHandler@checkForUpdates',
            ],

            // is a User related event.
            RequestedNewPassword::class        => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendNewPassword',
            ],
            // is a User related event.
            UserChangedEmail::class            => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeConfirmMail',
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeUndoMail',
            ],
            // admin related
            AdminRequestedTestMessage::class   => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendTestMessage',
            ],
            // is a Transaction Journal related event.
            StoredTransactionJournal::class    => [
                'FireflyIII\Handlers\Events\StoredJournalEventHandler@scanBills',
                'FireflyIII\Handlers\Events\StoredJournalEventHandler@processRules',
            ],
            // is a Transaction Journal related event.
            UpdatedTransactionJournal::class   => [
                'FireflyIII\Handlers\Events\UpdatedJournalEventHandler@scanBills',
                'FireflyIII\Handlers\Events\UpdatedJournalEventHandler@processRules',
            ],
        ];

    /**
     * @codeCoverageIgnore
     * Register any events for your application.
     */
    public function boot()
    {
        parent::boot();
        $this->registerDeleteEvents();
        $this->registerCreateEvents();
    }

    /**
     *
     */
    protected function registerCreateEvents()
    {
        // move this routine to a filter
        // in case of repeated piggy banks and/or other problems.
        PiggyBank::created(
            function (PiggyBank $piggyBank) {
                $repetition = new PiggyBankRepetition;
                $repetition->piggyBank()->associate($piggyBank);
                $repetition->startdate     = $piggyBank->startdate;
                $repetition->targetdate    = $piggyBank->targetdate;
                $repetition->currentamount = 0;
                $repetition->save();
            }
        );
    }

    /**
     *
     */
    protected function registerDeleteEvents()
    {

    }
}
