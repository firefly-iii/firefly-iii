<?php

namespace FireflyIII\Helpers\Reminders;

use FireflyIII\Models\Reminder;
use FireflyIII\Models\PiggyBank;
use Carbon\Carbon;

/**
 * Interface ReminderHelperInterface
 *
 * @package FireflyIII\Helpers\Reminders
 */
interface ReminderHelperInterface {
    /**
     * Takes a reminder, finds the piggy bank and tells you what to do now.
     * Aka how much money to put in.
     *
     * @param Reminder $reminder
     *
     * @return string
     */
    public function getReminderText(Reminder $reminder);

    /**
     * This routine will return an array consisting of two dates which indicate the start
     * and end date for each reminder that this piggy bank will have, if the piggy bank has
     * any reminders. For example:
     *
     * [12 mar - 15 mar]
     * [15 mar - 18 mar]
     *
     * etcetera.
     *
     * Array is filled with tiny arrays with Carbon objects in them.
     *
     * @param PiggyBank $piggyBank
     *
     * @return array
     */
    public function getReminderRanges(PiggyBank $piggyBank);

    /**
     * @param PiggyBank $piggyBank
     * @param Carbon    $start
     * @param Carbon    $end
     *
     * @return Reminder
     */
    public function createReminder(PiggyBank $piggyBank, Carbon $start, Carbon $end);
}