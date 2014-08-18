<?php

use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;

class PiggybankEvent extends Ardent
{

    public static $rules = [
        'piggybank_id' => 'required|exists:piggybanks,id',
        'date' => 'required|date',
        'amount' => 'required|numeric'
    ];

    /**
     * @return array
     */
    public static function factory()
    {
        $date = new Carbon;
        return [
            'piggybank_id' => 'factory|Piggybank',
            'date' => $date->format('Y-m-d'),
            'amount' => 10
        ];
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'date'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }

} 