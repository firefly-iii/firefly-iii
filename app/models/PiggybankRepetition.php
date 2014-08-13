<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;


/**
 * Class PiggybankRepetition
 */
class PiggybankRepetition extends Ardent
{
    public static $rules
        = [
            'piggybank_id'  => 'required|exists:piggybanks,id',
            'targetdate'    => 'date',
            'startdate'     => 'date',
            'currentamount' => 'required|numeric'
        ];

    /**
     * @return array
     */
    public static function factory()
    {
        $date = new Carbon;

        return [
            'piggybank_id'  => 'factory|Piggybank',
            'targetdate'    => $date,
            'startdate'     => $date,
            'currentamount' => 200
        ];
    }

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at', 'targetdate', 'startdate'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }

} 