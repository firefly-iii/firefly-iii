<?php
use LaravelBook\Ardent\Ardent;

class RecurringTransaction extends Ardent
{

    public static $rules
        = [
            'user_id'     => 'required|exists:users,id',
            'name'        => 'required|between:1,255',
            'match'       => 'required',
            'amount_max'  => 'required|between:0,65536',
            'amount_min'  => 'required|between:0,65536',
            'date'        => 'required|date',
            'active'      => 'required|between:0,1',
            'automatch'   => 'required|between:0,1',
            'repeat_freq' => 'required|in:daily,weekly,monthly,quarterly,half-year,yearly',
            'skip'        => 'required|between:0,31',
        ];

    public static $factory
        = [
            'user_id' => 'factory|User',
            'name'    => 'string',
            'data'    => 'string'
        ];

    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    public function next()
    {
        $start = clone $this->date;
        $skip = $this->skip == 0 ? 1 : $this->skip;

        while ($start <= $this->date) {
            switch ($this->repeat_freq) {
                case 'daily':
                    $start->addDays($skip);
                    break;
                case 'weekly':
                    $start->addWeeks($skip);
                    break;
                case 'monthly':
                    $start->addMonths($skip);
                    break;
                case 'quarterly':
                    $start->addMonths($skip);
                    break;
                case 'half-year':
                    $start->addMonths($skip * 6);
                    break;
                case 'yearly':
                    $this->addYears($skip);
                    break;

            }
        }

        return $start;
    }

    public function user()
    {
        return $this->belongsTo('User');
    }
} 