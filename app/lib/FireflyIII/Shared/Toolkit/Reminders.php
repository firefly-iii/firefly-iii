<?php

namespace FireflyIII\Shared\Toolkit;

use Carbon\Carbon;

/**
 * Class Reminders
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Reminders
{


    public function updateReminders()
    {
        $today           = Carbon::now()->format('Y-m-d');
        $reminders       = \Auth::user()->reminders()
                                ->where('startdate', '<=', $today)
                                ->where('enddate', '>=', $today)
                                ->where('active', '=', 1)
                                ->get();
        $hasTestReminder = false;

        /** @var \Reminder $reminder */
        foreach ($reminders as $reminder) {
            if ($reminder->title == 'Test' && intval($reminder->active) == 1) {
                $hasTestReminder = true;
            }
        }
        if (!$hasTestReminder) {
            $reminder = new \Reminder;
            $reminder->user()->associate(\Auth::user());
            $reminder->title     = 'Test';
            $reminder->startdate = new Carbon;
            $reminder->active    = 1;
            $reminder->enddate   = Carbon::now()->addDays(4);

            $data           = ['type'       => 'Test',
                               'action_uri' => route('index'),
                               'text'       => 'hello!',
                               'amount'     => 50,
                               'icon'       => 'fa-bomb'
            ];
            $reminder->data = $data;
            $reminder->save();
        }
    }

    /**
     *
     */
    public function getReminders()
    {
        $reminders = \Auth::user()->reminders()->where('active', true)->get();
        $return    = [];
        /** @var \Reminder $reminder */
        foreach ($reminders as $reminder) {
            $set = [
                'id' => $reminder->id
            ];
            switch ($reminder->data->type) {
                case 'Test':
                    $set['title'] = $reminder->title;
                    $set['icon']  = $reminder->data->icon;
                    $set['text']  = mf(floatval($reminder->data->amount));

            }
            $return[] = $set;
        }
        return $return;
    }
} 