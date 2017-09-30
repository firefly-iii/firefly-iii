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

declare(strict_types=1);

namespace FireflyIII\Providers;

use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
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
            // is a User related event.
            'FireflyIII\Events\RegisteredUser'            =>
                [
                    'FireflyIII\Handlers\Events\UserEventHandler@sendRegistrationMail',
                    'FireflyIII\Handlers\Events\UserEventHandler@attachUserRole',
                ],
            // is a User related event.
            'FireflyIII\Events\RequestedNewPassword'      => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendNewPassword',
            ],
            // is a User related event.
            'FireflyIII\Events\UserChangedEmail'          => [
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeConfirmMail',
                'FireflyIII\Handlers\Events\UserEventHandler@sendEmailChangeUndoMail',
            ],
            // admin related
            'FireflyIII\Events\AdminRequestedTestMessage' => [
                'FireflyIII\Handlers\Events\AdminEventHandler@sendTestMessage',
            ],
            // is a Transaction Journal related event.
            'FireflyIII\Events\StoredTransactionJournal'  =>
                [
                    'FireflyIII\Handlers\Events\StoredJournalEventHandler@scanBills',
                    'FireflyIII\Handlers\Events\StoredJournalEventHandler@connectToPiggyBank',
                    'FireflyIII\Handlers\Events\StoredJournalEventHandler@processRules',
                ],
            // is a Transaction Journal related event.
            'FireflyIII\Events\UpdatedTransactionJournal' =>
                [
                    'FireflyIII\Handlers\Events\UpdatedJournalEventHandler@scanBills',
                    'FireflyIII\Handlers\Events\UpdatedJournalEventHandler@processRules',
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
                Log::debug(sprintf('Now triggered journal delete response #%d', $journal->id));

                /** @var Transaction $transaction */
                foreach ($journal->transactions()->get() as $transaction) {
                    Log::debug(sprintf('Will now delete transaction #%d', $transaction->id));
                    $transaction->delete();
                }

                // also delete journal_meta entries.

                /** @var TransactionJournalMeta $meta */
                foreach ($journal->transactionJournalMeta()->get() as $meta) {
                    Log::debug(sprintf('Will now delete meta-entry #%d', $meta->id));
                    $meta->delete();
                }
            }
        );

    }

}
