<?php
use LaravelBook\Ardent\Ardent as Ardent;


/**
 * PiggybankRepetition
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $piggybank_id
 * @property \Carbon\Carbon $startdate
 * @property \Carbon\Carbon $targetdate
 * @property float $currentamount
 * @property-read \Piggybank $piggybank
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition wherePiggybankId($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereStartdate($value) 
 * @method static \Illuminate\Database\Query\Builder|\PiggybankRepetition whereTargetdate($value) 
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
    public function getDates()
    {
        return ['created_at', 'updated_at', 'targetdate', 'startdate'];
    }

    public function pct()
    {
        $total = $this->piggybank->targetamount;
        $saved = $this->currentamount;
        if ($total == 0) {
            return 0;
        }
        $pct = round(($saved / $total) * 100, 1);

        return $pct;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function piggybank()
    {
        return $this->belongsTo('Piggybank');
    }


} 