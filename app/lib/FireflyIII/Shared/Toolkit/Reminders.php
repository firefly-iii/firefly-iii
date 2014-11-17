<?php

namespace FireflyIII\Shared\Toolkit;
use Carbon\Carbon;

/**
 * Class Reminders
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Reminders {


    public function updateReminders() {
        $reminders = \Auth::user()->reminders()->get();
        $hasTestReminder = false;
        /** @var \Reminder $reminder */
        foreach($reminders as $reminder) {
            if($reminder->title == 'Test' && $reminder->active == true) {
                $hasTestReminder = true;
            }
        }
        if(!$hasTestReminder) {
            $reminder = new \Reminder;
            $reminder->user()->associate(\Auth::user());
            $reminder->title = 'Test';
            $reminder->startdate = new Carbon;
            $reminder->enddate = Carbon::now()->addDays(4);

            $data = [1 => 2, 'money' => 100, 'amount' => 2.45,'type' => 'Test'];

            $reminder->data = $data;
            $reminder->save();
        }
    }

    /**
     *
     */
    public function getReminders() {
        $reminders = \Auth::user()->reminders()->where('active',true)->get();
        $return = [];
        /** @var \Reminder $reminder */
        foreach($reminders as $reminder) {
            $set = [
                'id' => $reminder->id
            ];
            switch($reminder->data->type) {
                case 'Test':
                    $set['title'] = 'Test reminder #'.$reminder->id;
                    $set['icon'] = 'fa-bomb';
                    $set['text'] = 'Bla bla';

            }
            $return[] = $set;
        }
        return $return;
    }
} 