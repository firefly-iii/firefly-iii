<?php

namespace FireflyIII\Event;


use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

class Piggybank {

    /**
     * @param \Piggybank $piggybank
     * @param float      $amount
     */
    public function addMoney(\Piggybank $piggybank, $amount = 0.0) {
        if($amount > 0) {
            $event = new \PiggybankEvent;
            $event->piggybank()->associate($piggybank);
            $event->amount = floatval($amount);
            $event->date = new Carbon;
            if(!$event->validate()) {
                var_dump($event->errors());
                exit();
            }
            $event->save();
        }
    }

    /**
     * @param \Piggybank $piggybank
     * @param float      $amount
     */
    public function removeMoney(\Piggybank $piggybank, $amount = 0.0) {
        $amount = $amount * -1;
        if($amount < 0) {
            $event = new \PiggybankEvent;
            $event->piggybank()->associate($piggybank);
            $event->amount = floatval($amount);
            $event->date = new Carbon;
            if(!$event->validate()) {
                var_dump($event->errors());
                exit();
            }
            $event->save();
        }
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen('piggybank.addMoney', 'FireflyIII\Event\Piggybank@addMoney');
        $events->listen('piggybank.removeMoney', 'FireflyIII\Event\Piggybank@removeMoney');
    }
} 