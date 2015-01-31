<?php

use Watson\Validating\ValidatingTrait;

/**
 * Class Limit
 */
class BudgetLimit extends Eloquent
{

    use ValidatingTrait;
    public static $rules
        = [
            'budget_id'   => 'required|exists:budgets,id',
            'startdate'   => 'required|date',
            'amount'      => 'numeric|required|min:0.01',
            'repeats'     => 'required|boolean',
            'repeat_freq' => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly'

        ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function budget()
    {
        return $this->belongsTo('Budget');
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
