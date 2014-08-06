<?php
use LaravelBook\Ardent\Ardent;

class RecurringTransaction extends Ardent
{

    public static $rules
        = [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|between:1,255',
            'match' => 'required',
            'amount_max' => 'required|between:0,65536',
            'amount_min' => 'required|between:0,65536',
            'date' => 'required|date',
            'active' => 'required|between:0,1',
            'automatch' => 'required|between:0,1',
            'repeat_freq' => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly',
            'skip' => 'required|between:0,31',
        ];

    public static $factory
        = [
            'user_id' => 'factory|User',
            'name' => 'string',
            'data' => 'string'
        ];

    public function user()
    {
        return $this->belongsTo('User');
    }

} 