<?php

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Watson\Validating\ValidatingTrait;

/**
 * Class Limit
 */
class BudgetLimit extends Eloquent
{

    use ValidatingTrait;
    public static $rules
        = [
            'component_id' => 'required|exists:components,id',
            'startdate'    => 'required|date',
            'amount'       => 'numeric|required|min:0.01',
            'repeats'      => 'required|boolean',
            'repeat_freq'  => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly'

        ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('Budget', 'component_id');
    }

    /**
     * TODO see if this method is still used.
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

        if ($count == 0) {

            $repetition            = new \LimitRepetition();
            $repetition->startdate = $start;
            $repetition->enddate   = $end;
            $repetition->amount    = $this->amount;
            $repetition->budgetLimit()->associate($this);

            try {
                $repetition->save();
                \Log::debug('Created new repetition with id #' . $repetition->id);
            } catch (QueryException $e) {
                // do nothing

                \Log::error('Trying to save new Limitrepetition failed!');
                \Log::error($e->getMessage());
            }
            if (isset($repetition->id)) {
                \Event::fire('limits.repetition', [$repetition]); // not used, I guess?
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