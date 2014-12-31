<?php

namespace FireflyIII\Shared\Toolkit;

use Carbon\Carbon;
use FireflyIII\Exception\FireflyException;

/**
 * Class Navigation
 *
 * @package FireflyIII\Shared\Toolkit
 */
class Navigation
{
    /**
     * @return bool
     * @throws FireflyException
     */
    public function next()
    {
        /*
         * Get the start date and the range from the session
         */

        $filter = new Filter;

        $range = $filter->setSessionRangeValue();
        $start = \Session::get('start', Carbon::now()->startOfMonth());

        /*
         * Add some period to $start.
         */
        $next = $filter->next($range, clone $start);

        /*
         * Save in session:
         */
        \Session::put('start', $next);

        return true;
    }

    /**
     * @return bool
     * @throws FireflyException
     */
    public function prev()
    {
        /*
         * Get the start date and the range from the session
         */
        $filter = new Filter;

        $range = $filter->setSessionRangeValue();
        $start = \Session::get('start', Carbon::now()->startOfMonth());

        /*
         * Subtract some period to $start.
         */
        $prev = $filter->previous($range, clone $start);

        /*
         * Save in session:
         */
        \Session::put('start', $prev);

        return true;
    }
} 