<?php
/**
 * Controller.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use View;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** @var  string */
    protected $dateTimeFormat;
    /** @var string */
    protected $monthAndDayFormat;
    /** @var string */
    protected $monthFormat;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        View::share('hideBudgets', false);
        View::share('hideCategories', false);
        View::share('hideBills', false);
        View::share('hideTags', false);


        // translations:

        $this->middleware(
            function ($request, $next) {
                $this->monthFormat       = (string)trans('config.month');
                $this->monthAndDayFormat = (string)trans('config.month_and_day');
                $this->dateTimeFormat    = (string)trans('config.date_time');

                return $next($request);
            }
        );

    }

    /**
     * Take the array as returned by CategoryRepositoryInterface::spentPerDay and CategoryRepositoryInterface::earnedByDay
     * and sum up everything in the array in the given range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array  $array
     *
     * @return string
     */
    protected function getSumOfRange(Carbon $start, Carbon $end, array $array)
    {
        $sum          = '0';
        $currentStart = clone $start; // to not mess with the original one
        $currentEnd   = clone $end; // to not mess with the original one

        while ($currentStart <= $currentEnd) {
            $date = $currentStart->format('Y-m-d');
            if (isset($array[$date])) {
                $sum = bcadd($sum, $array[$date]);
            }
            $currentStart->addDay();
        }

        return $sum;
    }

}
