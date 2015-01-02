<?php

namespace FireflyIII\Event;


use Illuminate\Events\Dispatcher;

/**
 * Class Transaction
 *
 * @package FireflyIII\Event
 */
class Transaction
{
    /**
     * @param \Transaction $transaction
     */
    public function destroy(\Transaction $transaction)
    {
        \Cache::forget('account.' . $transaction->account_id . '.latestBalance');
        \Cache::forget('account.' . $transaction->account_id . '.lastActivityDate');

        // delete transaction:
        $transaction->delete();
    }

    /**
     * @param \Transaction $transaction
     */
    public function store(\Transaction $transaction)
    {
        \Cache::forget('account.' . $transaction->account_id . '.latestBalance');
        \Cache::forget('account.' . $transaction->account_id . '.lastActivityDate');
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // triggers when others are updated.
        $events->listen('transaction.store', 'FireflyIII\Event\Transaction@store');
        $events->listen('transaction.update', 'FireflyIII\Event\Transaction@update');
        $events->listen('transaction.destroy', 'FireflyIII\Event\Transaction@destroy');
    }

    /**
     * @param \Transaction $transaction
     */
    public function update(\Transaction $transaction)
    {
        \Cache::forget('account.' . $transaction->account_id . '.latestBalance');
        \Cache::forget('account.' . $transaction->account_id . '.lastActivityDate');
    }
} 
