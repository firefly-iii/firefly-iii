<?php

namespace FireflyIII\Event;


use Illuminate\Events\Dispatcher;

class Transaction
{
    public function destroy(\Transaction $transaction)
    {
        \Cache::forget('account.' . $transaction->account_id . '.latestBalance');
        \Cache::forget('account.' . $transaction->account_id . '.lastActivityDate');
    }

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

    public function update(\Transaction $transaction)
    {
        \Cache::forget('account.' . $transaction->account_id . '.latestBalance');
        \Cache::forget('account.' . $transaction->account_id . '.lastActivityDate');
    }
} 