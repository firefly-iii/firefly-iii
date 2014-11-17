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


    /**
     *
     */
    public function getReminders()
    {
        return [];
//        $reminders = \Auth::user()->reminders()->where('active', true)->get();
//        $return    = [];
//        /** @var \Reminder $reminder */
//        foreach ($reminders as $reminder) {
//            $set = ['id' => $reminder->id];
//            switch ($reminder->data->type) {
//                case 'Test':
//                case 'Piggybank':
//                    $set['title'] = $reminder->title;
//                    $set['icon']  = $reminder->data->icon;
//                    $set['text']  = mf(floatval($reminder->data->amount));
//                    break;
//
//            }
//            $return[] = $set;
//        }
//
//        return $return;
    }

    public function updateReminders()
    {
//        $today     = Carbon::now()->format('Y-m-d');
//        $reminders = \Auth::user()->reminders()->where('startdate', '<=', $today)->where('enddate', '>=', $today)->where('active', '=', 1)->get();
//
//        /*
//         * Find all piggy banks in the current set of reminders.
//         */
//        $this->updatePiggyBankReminders();
    }

    /**
     *
     */
    public function updatePiggyBankReminders()
    {
//        $piggyBanks = \Auth::user()->piggybanks()->where('targetdate', '>=', Carbon::now()->format('Y-m-d'))->whereNotNull('reminder')->where('remind_me', 1)
//                           ->get();
//
//        /** @var \FireflyIII\Shared\Toolkit\Date $dateKit */
//        $dateKit = \App::make('FireflyIII\Shared\Toolkit\Date');
//
//        $today = Carbon::now();
//
//
//        /** @var \Piggybank $piggyBank */
//        foreach ($piggyBanks as $piggyBank) {
//            /*
//             * Loop from today until end?
//             */
//            $end   = $piggyBank->targetdate;
//            $start = Carbon::now();
//
//            /*
//             * Create a reminder for the current period:
//             */
//            /*
//             * * type: Piggybank, Test
//             * action_uri: where to go when the user wants to do this?
//             * text: full text to present to user
//             * amount: any relevant amount.
//             * model: id of relevant model.
//             */
//
//            while ($start <= $end) {
//                $currentEnd = $dateKit->addPeriod(clone $start, $piggyBank->reminder, 0);
//
//                $count = \Reminder::where('startdate',$start->format('Y-m-d'))->where('enddate',$currentEnd->format('Y-m-d'))->count();
//                if ($start >= $today && $start <= $today && $count == 0) {
//
//
//                    $reminder         = new \Reminder;
//                    $reminder->active = 1;
//                    $reminder->user()->associate(\Auth::user());
//                    $reminder->startdate = clone $start;
//                    $reminder->enddate   = $currentEnd;
//                    $reminder->title     = 'Add money to "'.e($piggyBank->name).'"';
//                    $amount              = $piggyBank->amountPerReminder();
//                    $data                = ['type'                                                   => 'Piggybank', 'icon' => 'fa-sort-amount-asc', 'text' =>
//                        'If you want to save up the full amount of "' . e($piggyBank->name) . '", add ' . mf($amount) . ' to account "' . e(
//                            $piggyBank->account->name
//                        ) . '". Don\'t forget to connect the transfer to this piggy bank!', 'amount' => $amount, 'model' => $piggyBank->id
//
//                    ];
//                    $reminder->data      = $data;
//                    $reminder->save();
//                }
//                $start = $dateKit->addPeriod($start, $piggyBank->reminder, 0);
//            }
//
//        }

    }
} 