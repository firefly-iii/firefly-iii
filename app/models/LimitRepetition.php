<?php

use LaravelBook\Ardent\Ardent as Ardent;

/**
 * LimitRepetition
 *
 * @property integer        $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer        $limit_id
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $enddate
 * @property float          $amount
 * @property-read \Limit    $limit
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereLimitId($value)
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereEnddate($value)
 * @method static \Illuminate\Database\Query\Builder|\LimitRepetition whereAmount($value)
 */
class LimitRepetition extends Ardent
{
    public static $rules
        = [
            'limit_id'  => 'required|exists:limits,id',
            'startdate' => 'required|date',
            'enddate'   => 'required|date',
            'amount'    => 'numeric|required|min:0.01',
        ];

    /**
     * @return array
     */
    public static function factory()
    {
        $start = new \Carbon\Carbon;
        $start->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();

        return [
            'limit_id'  => 'factory|Limit',
            'startdate' => $start,
            'enddate'   => $end,
            'amount'    => 100
        ];
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * How much money is left in this?
     */
    public function left()
    {
        $left = floatval($this->amount);

        // budget:
        $budget = $this->limit->budget;

        /** @var \Firefly\Storage\Limit\EloquentLimitRepository $limits */
        $limits = App::make('Firefly\Storage\Limit\EloquentLimitRepository');
        $set = $limits->getTJByBudgetAndDateRange($budget, $this->startdate, $this->enddate);

        foreach ($set as $journal) {
            foreach ($journal->transactions as $t) {
                if ($t->amount < 0) {
                    $left += floatval($t->amount);
                }
            }
        }

        return $left;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function limit()
    {
        return $this->belongsTo('Limit');
    }

    /**
     * Returns a string used to sort this particular repetition
     * based on the date and period it falls into. Ie. the limit
     * repeats monthly and the start date is 12 dec 2012, this will
     * return 2012-12.
     */
    public function periodOrder()
    {
        if (is_null($this->repeat_freq)) {
            $this->repeat_freq = $this->limit->repeat_freq;
        }
        switch ($this->repeat_freq) {
            default:
                throw new \Firefly\Exception\FireflyException('No date formats for frequency "' . $this->repeat_freq
                    . '"!');
                break;
            case 'daily':
                return $this->startdate->format('Ymd') . '-5';
                break;
            case 'weekly':
                return $this->startdate->format('Ymd') . '-4';
                break;
            case 'monthly':
                return $this->startdate->format('Ymd') . '-3';
                break;
            case 'quarterly':
                return $this->startdate->format('Ymd') . '-2';
                break;
            case 'half-year':
                return $this->startdate->format('Ymd') . '-1';
                break;
            case 'yearly':
                return $this->startdate->format('Ymd') . '-0';
                break;
        }
    }

    /**
     * Same as above, just with a more natural view. So "March 2012".
     */
    public function periodShow()
    {
        if (is_null($this->repeat_freq)) {
            $this->repeat_freq = $this->limit->repeat_freq;
        }
        switch ($this->repeat_freq) {
            default:
                throw new \Firefly\Exception\FireflyException('No date formats for frequency "' . $this->repeat_freq
                    . '"!');
                break;
            case 'daily':
                return $this->startdate->format('j F Y');
                break;
            case 'weekly':
                return $this->startdate->format('\W\e\e\k W, Y');
                break;
            case 'monthly':
                return $this->startdate->format('F Y');
                break;
            case 'half-year':
            case 'quarterly':
                return $this->startdate->format('M Y') . ' - ' . $this->enddate->format('M Y');
                break;
            case 'yearly':
                return $this->startdate->format('Y');
                break;
        }
    }


} 