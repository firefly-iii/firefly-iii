<?php

/**
 * Class ReminderController
 *
 */
class ReminderController extends BaseController
{

    /**
     * @param Reminder $reminder
     */
    public function show(Reminder $reminder)
    {
        var_dump($reminder);
    }
}