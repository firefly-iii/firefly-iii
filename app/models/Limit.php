<?php

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use LaravelBook\Ardent\Ardent as Ardent;

/**
 * Limit
 *
 * @property integer                                                          $id
 * @property \Carbon\Carbon                                                   $created_at
 * @property \Carbon\Carbon                                                   $updated_at
 * @property integer                                                          $component_id
 * @property \Carbon\Carbon                                                   $startdate
 * @property float                                                            $amount
 * @property boolean                                                          $repeats
 * @property string                                                           $repeat_freq
 * @property-read \Budget                                                     $budget
 * @property-read \Component                                                  $component
 * @property-read \Illuminate\Database\Eloquent\Collection|\LimitRepetition[] $limitrepetitions
 * @method static \Illuminate\Database\Query\Builder|\Limit whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereComponentId($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereStartdate($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereAmount($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereRepeats($value)
 * @method static \Illuminate\Database\Query\Builder|\Limit whereRepeatFreq($value)
 */
class Limit extends Ardent
{

    public static $rules
        = ['component_id' => 'required|exists:components,id', 'startdate' => 'required|date', 'amount' => 'numeric|required|min:0.01',
           'repeats'      => 'required|boolean', 'repeat_freq' => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly'

        ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('Budget', 'component_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function component()
    {
        return $this->belongsTo('Component', 'component_id');
    }

    /**
     * Create a new repetition for this limit, starting on
     * the given date.
     *
     * @param Carbon $start
     */
    public function createRepetition(Carbon $start)
    {

        $end = clone $start;
        // go to end:
        switch ($this->repeat_freq) {
            case 'daily':
                $end->addDay();
                break;
            case 'weekly':
                $end->addWeek();
                break;
            case 'monthly':
                $end->addMonth();
                break;
            case 'quarterly':
                $end->addMonths(3);
                break;
            case 'half-year':
                $end->addMonths(6);
                break;
            case 'yearly':
                $end->addYear();
                break;
        }
        $end->subDay();
        $count = $this->limitrepetitions()->where('startdate', $start->format('Y-m-d'))->where('enddate', $end->format('Y-m-d'))->count();
        \Log::debug('All: ' . $this->limitrepetitions()->count() . ' (#' . $this->id . ')');
        \Log::debug('Found ' . $count . ' limit-reps for limit #' . $this->id . ' with start ' . $start->format('Y-m-d') . ' and end ' . $end->format('Y-m-d'));

        if ($count == 0) {

            $repetition            = new \LimitRepetition();
            $repetition->startdate = $start;
            $repetition->enddate   = $end;
            $repetition->amount    = $this->amount;
            $repetition->limit()->associate($this);

            try {
                $repetition->save();
                \Log::debug('Created new repetition with id #' . $repetition->id);
            } catch (QueryException $e) {
                // do nothing

                \Log::error('Trying to save new Limitrepetition failed!');
                \Log::error($e->getMessage());
            }
            if (isset($repetition->id)) {
                \Event::fire('limits.repetition', [$repetition]);
            }
        } else {
            if ($count == 1) {
                // update this one:
                $repetition         = $this->limitrepetitions()->where('startdate', $start->format('Y-m-d'))->where('enddate', $end->format('Y-m-d'))->first();
                $repetition->amount = $this->amount;
                $repetition->save();

            }
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function limitrepetitions()
    {
        return $this->hasMany('LimitRepetition');
    }


    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }


} 