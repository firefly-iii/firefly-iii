<?php
namespace FireflyIII\Event;

use Illuminate\Events\Dispatcher;


/**
 * Class Event
 *
 * @package FireflyIII\Event
 */
class Event
{

    /**
     * @param \Account $account
     *
     * @throws \Exception
     */
    public function deleteAccount(\Account $account)
    {
        // get piggy banks
        $piggies = $account->piggyBanks()->get();

        // get reminders for each
        /** @var \PiggyBank $piggyBank */
        foreach ($piggies as $piggyBank) {
            $reminders = $piggyBank->reminders()->get();
            /** @var \Reminder $reminder */
            foreach ($reminders as $reminder) {
                $reminder->delete();
            }
        }
    }


    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // triggers when others are updated.
        $events->listen('account.destroy', 'FireflyIII\Event\Event@deleteAccount');
    }
}