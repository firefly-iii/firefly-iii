<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 27/07/14
 * Time: 16:28
 */

namespace Firefly\Helper\Controllers;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class Budget
 *
 * @package Firefly\Helper\Controllers
 */
class Budget implements BudgetInterface
{

    /**
     * @param Collection $budgets
     *
     * @return mixed|void
     */
    public function organizeByDate(Collection $budgets)
    {
        $return = [];

        foreach ($budgets as $budget) {
            foreach ($budget->limits as $limit) {
                $dateFormats = \Config::get('firefly.date_formats_by_period.' . $limit->repeat_freq);
                if (is_null($dateFormats)) {
                    throw new \Firefly\Exception\FireflyException('No date formats for ' . $limit->repeat_freq);
                }

                foreach ($limit->limitrepetitions as $rep) {
                    $periodOrder = $rep->startdate->format($dateFormats['group_date']);
                    $period = $rep->startdate->format($dateFormats['display_date']);
                    $return[$periodOrder] = isset($return[$periodOrder]) ? $return[$periodOrder] : ['date' => $period];

                }
            }
        }
        // put all the budgets under their respective date:
        foreach ($budgets as $budget) {
            foreach ($budget->limits as $limit) {
                $dateFormats = \Config::get('firefly.date_formats_by_period.' . $limit->repeat_freq);
                foreach ($limit->limitrepetitions as $rep) {
                    $rep->left = $rep->left();

                    $month = $rep->startdate->format($dateFormats['group_date']);
                    $return[$month]['limitrepetitions'][] = $rep;
                }
            }
        }
        krsort($return);
        return $return;
    }

} 