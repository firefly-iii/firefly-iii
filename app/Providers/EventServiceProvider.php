<?php
/**
 * EventServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Providers;

use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Class EventServiceProvider
 *
 * @package FireflyIII\Providers
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen
        = [
            'FireflyIII\Events\TransactionJournalUpdated' => [
                'FireflyIII\Handlers\Events\ScanForBillsAfterUpdate',
                'FireflyIII\Handlers\Events\UpdateJournalConnection',
                'FireflyIII\Handlers\Events\FireRulesForUpdate',

            ],

            'FireflyIII\Events\BudgetLimitStored'        => [
                'FireflyIII\Handlers\Events\BudgetLimitEventHandler@store',
            ],
            'FireflyIII\Events\BudgetLimitUpdated'       => [
                'FireflyIII\Handlers\Events\BudgetLimitEventHandler@update',
            ],
            'FireflyIII\Events\TransactionStored'        => [
                'FireflyIII\Handlers\Events\ConnectTransactionToPiggyBank',
            ],
            'FireflyIII\Events\TransactionJournalStored' => [
                'FireflyIII\Handlers\Events\ScanForBillsAfterStore',
                'FireflyIII\Handlers\Events\ConnectJournalToPiggyBank',
                'FireflyIII\Handlers\Events\FireRulesForStore',
            ],
            'Illuminate\Auth\Events\Logout'              => [
                'FireflyIII\Handlers\Events\UserEventListener@onUserLogout',
            ],
            'FireflyIII\Events\UserRegistration'         => [
                'FireflyIII\Handlers\Events\SendRegistrationMail',
                'FireflyIII\Handlers\Events\AttachUserRole',
                'FireflyIII\Handlers\Events\UserConfirmation@sendConfirmation',
            ],
            'FireflyIII\Events\ResendConfirmation'       => [
                'FireflyIII\Handlers\Events\UserConfirmation@resendConfirmation',
            ],
        ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);
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
                $repetition->startdate     = is_null($piggyBank->startdate) ? null : $piggyBank->startdate;
                $repetition->targetdate    = is_null($piggyBank->targetdate) ? null : $piggyBank->targetdate;
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
        Account::deleted(
            function (Account $account) {

                /** @var Transaction $transaction */
                foreach ($account->transactions()->get() as $transaction) {
                    $journal = $transaction->transactionJournal()->first();
                    $journal->delete();
                }
            }
        );

    }

}
