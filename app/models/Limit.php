<?php

use LaravelBook\Ardent\Ardent as Ardent;

/**
 * Limit
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $component_id
 * @property \Carbon\Carbon $startdate
 * @property float $amount
 * @property boolean $repeats
 * @property string $repeat_freq
 * @property-read \Component $component
 * @property-read \Budget $budget
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
        = [
            'component_id' => 'required|exists:components,id',
            'startdate'    => 'required|date',
            'amount'       => 'numeric|required|min:0.01',
            'repeats'      => 'required|between:0,1',
            'repeat_freq'  => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly'

        ];

    public static $factory
        = [
            'component_id' => 'factory|Budget',
            'startdate'    => 'date',
            'enddate'      => 'date',
            'amount'       => '100'
        ];

    public function component()
    {
        return $this->belongsTo('Component','component_id');
    }

    public function budget()
    {
        return $this->belongsTo('Budget', 'component_id');
    }

    public function limitrepetitions() {
        return $this->hasMany('LimitRepetition');
    }

    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }


} 