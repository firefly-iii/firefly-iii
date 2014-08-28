<?php

namespace Firefly\Storage\Reminder;


use Carbon\Carbon;

/**
 * Class EloquentReminderRepository
 *
 * @package Firefly\Storage\Reminder
 */
class EloquentReminderRepository implements ReminderRepositoryInterface
{
    /**
     * @param \Reminder $reminder
     *
     * @return mixed|void
     */
    public function deactivate(\Reminder $reminder)
    {
        $reminder->active = 0;
        $reminder->save();

        return $reminder;
    }

    /**
     * @param $id
     *
     * @return mixed|void
     */
    public function find($id)
    {
        return \Reminder::find($id);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $today = new Carbon;

        return \Auth::user()->reminders()->validOn($today)->get();
    }

    /**
     *
     */
    public function getCurrentRecurringReminders()
    {
        $today = new Carbon;

        return \Auth::user()->reminders()->with('recurringtransaction')->validOn($today)->where(
            'class', 'RecurringTransactionReminder'
        )->get();

    }

} 