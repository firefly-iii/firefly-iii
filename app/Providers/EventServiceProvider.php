<?php
/**
 * EventServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Providers;

use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Log;

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
            // new event handlers:
            'FireflyIII\Events\ConfirmedUser'      => // is a User related event.
                [
                    'FireflyIII\Handlers\Events\UserEventHandler@storeConfirmationIpAddress',
                ],
            'FireflyIII\Events\RegisteredUser'     => // is a User related event.
                [
                    'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationMail',
                    'FireflyIII\Handlers\Events\UserEventHandler@attachUserRole',
                    'FireflyIII\Handlers\Events\UserEventHandler@sendConfirmationMessage',
                    'FireflyIII\Handlers\Events\UserEventHandler@storeRegistrationIpAddress',
                ],
            'FireflyIII\Events\ResentConfirmation' => // is a User related event.
                [
                    'FireflyIII\Handlers\Events\UserEventHandler@sendConfirmationMessageAgain',
                ],
            'FireflyIII\Events\StoredBudgetLimit'  => // is a Budget related event.
                [
                    'FireflyIII\Handlers\Events\BudgetEventHandler@storeRepetition',
                ],

            'FireflyIII\Events\UpdatedBudgetLimit' => // is a Budget related event.
                [
                    'FireflyIII\Handlers\Events\BudgetEventHandler@updateRepetition',
                ],

            'FireflyIII\Events\StoredTransactionJournal'  => // is a Transaction Journal related event.
                [
                    'FireflyIII\Handlers\Events\StoredJournalEventHandler@scanBills',
                    'FireflyIII\Handlers\Events\StoredJournalEventHandler@connectToPiggyBank',
                    'FireflyIII\Handlers\Events\StoredJournalEventHandler@processRules',
                ],
            'FireflyIII\Events\UpdatedTransactionJournal' => // is a Transaction Journal related event.
                [
                    'FireflyIII\Handlers\Events\UpdatedJournalEventHandler@scanBills',
                    'FireflyIII\Handlers\Events\UpdatedJournalEventHandler@processRules',
                ],

            // LARAVEL EVENTS:
            'Illuminate\Auth\Events\Logout'               =>
                [
                    'FireflyIII\Handlers\Events\UserEventHandler@logoutUser',
                ],
        ];

    /**
     * Register any events for your application.
     *
     * @return void
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
                Log::debug('Now trigger account delete response #' . $account->id);
                /** @var Transaction $transaction */
                foreach ($account->transactions()->get() as $transaction) {
                    Log::debug('Now at transaction #' . $transaction->id);
                    $journal = $transaction->transactionJournal()->first();
                    if (!is_null($journal)) {
                        Log::debug('Call for deletion of journal #' . $journal->id);
                        $journal->delete();
                    }
                }
            }
        );

        TransactionJournal::deleted(
            function (TransactionJournal $journal) {
                Log::debug('Now triggered journal delete response #' . $journal->id);

                /** @var Transaction $transaction */
                foreach ($journal->transactions()->get() as $transaction) {
                    Log::debug('Will now delete transaction #' . $transaction->id);
                    $transaction->delete();
                }
            }
        );

    }

}
