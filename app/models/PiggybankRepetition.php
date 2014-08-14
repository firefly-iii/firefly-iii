<?php
use Carbon\Carbon;
use LaravelBook\Ardent\Ardent as Ardent;


/**
 * Class PiggybankRepetition
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $piggybank_id
 * @property \Carbon\Carbon $targetdate
 * @property \Carbon\Carbon $startdate
 * @property float $currentamount
 * @property-read \Piggybank $piggybank
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition wherePiggybankId($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereTargetdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereStartdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereCurrentamount($value) 
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