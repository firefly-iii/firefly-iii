<?php

namespace FireflyIII\Event;


use Illuminate\Events\Dispatcher;

/**
 * Class Account
 *
 * @package FireflyIII\Event
 */
class Account
{
    /**
     * @param \Account $account
     */
    public function destroy(\Account $account)
    {
        \Cache::forget('account.' . $account->id . '.latestBalance');
        \Cache::forget('account.' . $account->id . '.lastActivityDate');
    }

    /**
     * @param \Account $account
     */
    public function store(\Account $account)
    {

        \Cache::forget('account.' . $account->id . '.latestBalance');
        \Cache::forget('account.' . $account->id . '.lastActivityDate');
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // triggers when others are updated.
        $events->listen('account.store', 'FireflyIII\Event\Account@store');
        $events->listen('account.update', 'FireflyIII\Event\Account@update');
        $events->listen('account.destroy', 'FireflyIII\Event\Account@destroy');
    }

    /**
     * @param \Account $account
     */
    public function update(\Account $account)
    {
        \Cache::forget('account.' . $account->id . '.latestBalance');
        \Cache::forget('account.' . $account->id . '.lastActivityDate');
    }
} 
