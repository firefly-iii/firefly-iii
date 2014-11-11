<?php

namespace Firefly\Helper\Toolkit;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class Toolkit
 *
 * @package Firefly\Helper\Toolkit
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Toolkit implements ToolkitInterface
{


    /**
     * Lots of code in Firefly III still depends on session['start'], session['end'] and
     * session['range'] to be available, even though this feature has been removed from Firefly
     * in favor of a new reporting feature. This reporting feature can show the user past and future
     * date ranges instead of the dashboard (the dashboard always shows "right now").
     *
     * The only actual choice the user is left with is the range, which can be changed using the Preferences pane.
     *
     * The start/end dates are set here, regardless of what the user might want to see.
     *
     * @return null
     */
    public function getDateRange()
    {
        /*
         * Get all data from the session:
         */
        $range = $this->_getRange();
        #\Log::debug('Range is: ' . $range);
        $start = \Session::has('start') ? \Session::get('start') : new Carbon;

        #\Log::debug('Session start is: ' . $start->format('Y-m-d'));
        $end = \Session::has('end') ? \Session::get('end') : new Carbon;
        #\Log::debug('Session end is  : ' . $end->format('Y-m-d'));

        /*
         * Force start date to at the start of the $range.
         * Ie. the start of the week, month, year.
         */
        $start = $this->_updateStartDate($range, $start);
        #\Log::debug('After update, session start is: ' . $start->format('Y-m-d'));

        /*
         * Force end date to at the END of the $range. Always based on $start.
         * Ie. the END of the week, month, year.
         */
        $end = $this->_updateEndDate($range, $start);
        #\Log::debug('After update, session end is  : ' . $end->format('Y-m-d'));

        /*
         * get the name of the month, depending on the range. Purely for astetics
         */
        $period = $this->_periodName($range, $start);

        /*
         * Get the date for the previous and next period.
         * Ie. next week, next month, etc.
         */
        $prev = $this->_previous($range, clone $start);
        $next = $this->_next($range, clone $start);

        /*
         * Save everything in the session:
         */
        \Session::put('start', $start);
        \Session::put('end', $end);
        \Session::put('range', $range);
        \Session::put('period', $period);
        \Session::put('prev', $this->_periodName($range, $prev));
        \Session::put('next', $this->_periodName($range, $next));
        return null;

    }

    /**
     *
     */
    public function checkImportJobs()
    {
        /*
         * Get all jobs.
         */
        /** @var \Importmap $importJob */
        $importJob = \Importmap::where('user_id', \Auth::user()->id)
                               ->where('totaljobs', '>', \DB::Raw('`jobsdone`'))
                               ->orderBy('created_at', 'DESC')
                               ->first();
        if (!is_null($importJob)) {
            $diff  = intval($importJob->totaljobs) - intval($importJob->jobsdone);
            $date  = new Carbon;
            $today = new Carbon;
            $date->addSeconds($diff);
            \Session::put('job_pct', $importJob->pct());
            \Session::put('job_text', $date->diffForHumans());
        } else {
            \Session::forget('job_pct');
            \Session::forget('job_text');
        }
    }

    protected function _getRange()
    {
        if (!is_null(\Session::get('range'))) {
            $range = \Session::get('range');
        } else {
            /** @noinspection PhpUndefinedClassInspection */
            $preferences = \App::make('Firefly\Helper\Preferences\PreferencesHelperInterface');
            $viewRange   = $preferences->get('viewRange', '1M');

            // default range:
            $range = $viewRange->data;
            \Session::put('range', $range);
        }
        return $range;

    }

    /**
     * @param        $range
     * @param Carbon $start
     *
     * @return Carbon
     */
    protected function _updateStartDate($range, Carbon $start)
    {
        switch ($range) {
            case '1D':
                $start->startOfDay();
                break;
            case '1W':
                $start->startOfWeek();
                break;
            case '1M':
                $start->startOfMonth();
                break;
            case '3M':
                $start->firstOfQuarter();
                break;
            case '6M':
                if (intval($start->format('m')) >= 7) {
                    $start->startOfYear()->addMonths(6);
                } else {
                    $start->startOfYear();
                }
                break;
            case '1Y':
                $start->startOfYear();
                break;
        }

        return $start;

    }

    /**
     * @param        $range
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Carbon
     */
    protected function _updateEndDate($range, Carbon $start)
    {
        $end = clone $start;
        switch ($range) {
            default:
                throw new FireflyException('_updateEndDate cannot handle $range ' . $range);
                break;
            case '1D':
                $end->endOfDay();
                break;
            case '1W':
                $end->endOfWeek();
                break;
            case '1M':
                $end->endOfMonth();
                break;
            case '3M':
                $end->lastOfQuarter();
                break;
            case '6M':
                if (intval($start->format('m')) >= 7) {
                    $end->endOfYear();
                } else {
                    $end->startOfYear()->addMonths(6);
                }
                break;
            case '1Y':
                $end->endOfYear();
                break;

        }

        return $end;
    }

    protected function _periodName($range, Carbon $date)
    {
        switch ($range) {
            default:
                throw new FireflyException('No _periodName() for range "' . $range . '"');
                break;
            case '1D':
                return $date->format('jS F Y');
                break;
            case '1W':
                return 'week ' . $date->format('W, Y');
                break;
            case '1M':
                return $date->format('F Y');
                break;
            case '3M':
                $month = intval($date->format('m'));
                return 'Q' . ceil(($month / 12) * 4) . ' ' . $date->format('Y');
                break;
            case '6M':
                $month    = intval($date->format('m'));
                $half     = ceil(($month / 12) * 2);
                $halfName = $half == 1 ? 'first' : 'second';
                return $halfName . ' half of ' . $date->format('d-m-Y');
                break;
            case '1Y':
                return $date->format('Y');
                break;


        }
    }

    protected function _previous($range, Carbon $date)
    {
        switch ($range) {
            default:
                throw new FireflyException('Cannot do _previous() on ' . $range);
                break;
            case '1D':
                $date->startOfDay()->subDay();
                break;
            case '1W':
                $date->startOfWeek()->subWeek();
                break;
            case '1M':
                $date->startOfMonth()->subMonth();
                break;
            case '3M':
                $date->firstOfQuarter()->subMonths(3)->firstOfQuarter();
                break;
            case '6M':
                $month = intval($date->format('m'));
                if ($month <= 6) {
                    $date->startOfYear()->subMonths(6);
                } else {
                    $date->startOfYear();
                }
                break;
            case '1Y':
                $date->startOfYear()->subYear();
                break;

        }
        return $date;
    }

    protected function _next($range, Carbon $date)
    {
        switch ($range) {
            case '1D':
                $date->endOfDay()->addDay();
                break;
            case '1W':
                $date->endOfWeek()->addDay()->startOfWeek();
                break;
            case '1M':
                $date->endOfMonth()->addDay()->startOfMonth();
                break;
            case '3M':
                $date->lastOfQuarter()->addDay();
                break;
            case '6M':
                if (intval($date->format('m')) >= 7) {
                    $date->startOfYear()->addYear();
                } else {
                    $date->startOfYear()->addMonths(6);
                }
                break;
            case '1Y':
                $date->startOfYear()->addYear();
                break;
            default:
                throw new FireflyException('Cannot do _next() on ' . $range);
                break;
        }
        return $date;
    }

    public function next()
    {
        /*
         * Get the start date and the range from the session
         */
        $range = $this->_getRange();
        $start = \Session::get('start');

        /*
         * Add some period to $start.
         */
        $next = $this->_next($range, clone $start);

        /*
         * Save in session:
         */
        \Session::put('start', $next);
        return true;
    }

    public function prev()
    {
        /*
         * Get the start date and the range from the session
         */
        $range = $this->_getRange();
        $start = \Session::get('start');

        /*
         * Substract some period to $start.
         */
        $prev = $this->_previous($range, clone $start);

        /*
         * Save in session:
         */
        \Session::put('start', $prev);
        return true;
    }

    /**
     * Takes any collection and tries to make a sensible select list compatible array of it.
     *
     * @param Collection $set
     * @param null $titleField
     *
     * @return mixed
     */
    public function makeSelectList(Collection $set, $titleField = null)
    {
        $selectList = [];
        /** @var Model $entry */
        foreach ($set as $entry) {
            $id    = intval($entry->id);
            $title = null;
            if (is_null($titleField)) {
                // try 'title' field.
                if (isset($entry->title)) {
                    $title = $entry->title;
                }
                // try 'name' field
                if (is_null($title)) {
                    $title = $entry->name;
                }

                // try 'description' field
                if (is_null($title)) {
                    $title = $entry->description;
                }
            } else {
                $title = $entry->$titleField;
            }
            $selectList[$id] = $title;
        }
        return $selectList;
    }

    /**
     * @param string $start
     * @param string $end
     * @param int $steps
     */
    public function colorRange($start, $end, $steps = 5)
    {
        if (strlen($start) != 6) {
            throw new FireflyException('Start, ' . e($start) . ' should be a six character HTML colour.');
        }
        if (strlen($end) != 6) {
            throw new FireflyException('End, ' . e($end) . ' should be a six character HTML colour.');
        }
        if ($steps < 1) {
            throw new FireflyException('Steps must be > 1');
        }

        $start = '#' . $start;
        $end   = '#' . $end;
        /*
         * Split html colours.
         */
        list($rs, $gs, $bs) = sscanf($start, "#%02x%02x%02x");
        list($re, $ge, $be) = sscanf($end, "#%02x%02x%02x");

        $stepr = ($re - $rs) / $steps;
        $stepg = ($ge - $gs) / $steps;
        $stepb = ($be - $bs) / $steps;

        $return = [];
        for ($i = 0; $i <= $steps; $i++) {
            $cr = $rs + ($stepr * $i);
            $cg = $gs + ($stepg * $i);
            $cb = $bs + ($stepb * $i);

            $return[] = $this->rgb2html($cr, $cg, $cb);
        }

        return $return;
    }

    protected function rgb2html($r, $g = -1, $b = -1)
    {
        $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
        $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
        $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

        $color = (strlen($r) < 2 ? '0' : '') . $r;
        $color .= (strlen($g) < 2 ? '0' : '') . $g;
        $color .= (strlen($b) < 2 ? '0' : '') . $b;
        return '#' . $color;
    }

    /**
     * @param Carbon $currentEnd
     * @param $repeatFreq
     * @throws FireflyException
     */
    public function endOfPeriod(Carbon $currentEnd, $repeatFreq)
    {
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do getFunctionForRepeatFreq for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $currentEnd->addDay();
                break;
            case 'weekly':
                $currentEnd->addWeek()->subDay();
                break;
            case 'monthly':
                $currentEnd->addMonth()->subDay();
                break;
            case 'quarterly':
                $currentEnd->addMonths(3)->subDay();
                break;
            case 'half-year':
                $currentEnd->addMonths(6)->subDay();
                break;
            case 'yearly':
                $currentEnd->addYear()->subDay();
                break;
        }
    }

    /**
     * @param Carbon $date
     * @param $repeatFreq
     * @param $skip
     * @return Carbon
     * @throws FireflyException
     */
    public function addPeriod(Carbon $date, $repeatFreq, $skip)
    {
        $add = ($skip + 1);
        switch ($repeatFreq) {
            default:
                throw new FireflyException('Cannot do getFunctionForRepeatFreq for $repeat_freq ' . $repeatFreq);
                break;
            case 'daily':
                $date->addDays($add);
                break;
            case 'weekly':
                $date->addWeeks($add);
                break;
            case 'monthly':
                $date->addMonths($add);
                break;
            case 'quarterly':
                $months = $add * 3;
                $date->addMonths($months);
                break;
            case 'half-year':
                $months = $add * 6;
                $date->addMonths($months);
                break;
            case 'yearly':
                $date->addYears($add);
                break;
        }
        return $date;
    }
}