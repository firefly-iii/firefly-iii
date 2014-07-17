<?php

use LaravelBook\Ardent\Ardent as Ardent;

class Limit extends Ardent
{

    public static $rules
        = [
            'component_id' => 'required|exists:components,id',
            'startdate'    => 'required|date',
            'enddate'      => 'required|date',
            'amount'       => 'numeric|required|min:0.01'

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
        return $this->belongsTo('Component');
    }

    public function budget()
    {
        return $this->belongsTo('Budget', 'component_id');
    }

    public function getDates()
    {
        return ['created_at', 'updated_at', 'startdate', 'enddate'];
    }


} 