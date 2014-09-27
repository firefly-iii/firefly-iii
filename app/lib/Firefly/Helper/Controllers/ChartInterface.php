<?php

namespace Firefly\Helper\Controllers;


use Carbon\Carbon;

/**
 * Interface ChartInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface ChartInterface
{

    /**
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function account(\Account $account, Carbon $start, Carbon $end);

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function categories(Carbon $start, Carbon $end);

    /**
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return mixed
     */
    public function accountDailySummary(\Account $account, Carbon $date);

    /**
     * @param \Category $category
     * @param           $range
     * @param Carbon    $start
     * @param Carbon    $end
     *
     * @return mixed
     */
    public function categoryShowChart(\Category $category, $range, Carbon $start, Carbon $end);

    /**
     * @param \Budget $budget
     * @param Carbon  $date
     *
     * @return float|null
     */
    public function spentOnDay(\Budget $budget, Carbon $date);

    /**
     * @param \Budget $budget
     *
     * @return int[]
     */
    public function allJournalsInBudgetEnvelope(\Budget $budget);

    /**
     * @param \Budget $budget
     * @param array   $ids
     *
     * @return mixed
     */
    public function journalsNotInSet(\Budget $budget, array $ids);

    /**
     * @param array $set
     *
     * @return mixed
     */
    public function transactionsByJournals(array $set);

    /**
     * Get all limit (LimitRepetitions) for a budget falling in a certain date range.
     *
     * @param \Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function limitsInRange(\Budget $budget, Carbon $start, Carbon $end);


    /**
     * Firefly checks how much money has been spend on the limitrepetition (aka: the current envelope) in
     * the period denoted. Aka, the user has a certain amount of money in an envelope and wishes to know how
     * much he has spent between the dates entered. This date range can be a partial match with the date range
     * of the envelope or no match at all.
     *
     * @param \LimitRepetition $repetition
     * @param Carbon           $start
     * @param Carbon           $end
     *
     * @return mixed
     */
    public function spentOnLimitRepetitionBetweenDates(\LimitRepetition $repetition, Carbon $start, Carbon $end);


}