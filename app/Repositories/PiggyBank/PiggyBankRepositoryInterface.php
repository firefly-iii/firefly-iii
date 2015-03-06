<?php

namespace FireflyIII\Repositories\PiggyBank;

use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Reminder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Interface PiggyBankRepositoryInterface
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
interface PiggyBankRepositoryInterface
{


    /**
     * @SuppressWarnings("CyclomaticComplexity") // It's exactly 5. So I don't mind.
     *
     * Based on the piggy bank, the reminder-setting and
     * other variables this method tries to divide the piggy bank into equal parts. Each is
     * accommodated by a reminder (if everything goes to plan).
     *
     * @param PiggyBankRepetition $repetition
     *
     * @return Collection
     */
    public function calculateParts(PiggyBankRepetition $repetition);

    /**
     * @param array $data
     *
     * @return PiggyBankPart
     */
    public function createPiggyBankPart(array $data);

    /**
     * @param PiggyBank $piggyBank
     * @param Carbon    $currentStart
     * @param Carbon    $currentEnd
     *
     * @return Reminder
     */
    public function createReminder(PiggyBank $piggyBank, Carbon $currentStart, Carbon $currentEnd);

    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data);

    /**
     * @param PiggyBank $account
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data);

    /**
     * Takes a reminder, finds the piggy bank and tells you what to do now.
     * Aka how much money to put in.
     *
     * TODO the routine to calculate the number of reminders is probably the same
     * routine as is used in the Reminders-middle ware and can be used again.
     *
     *
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
}