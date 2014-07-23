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

    public function limit()
    {
        return $this->belongsTo('Limit');
    }

    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }

    /**
     * How much money is left in this?
     */
    public function left()
    {
        $key = 'limit-rep-left-' . $this->id;
        if (Cache::has($key)) {
            return Cache::get($key);
        }
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
        Cache::forever($key, $left);


        return $left;
    }


} 