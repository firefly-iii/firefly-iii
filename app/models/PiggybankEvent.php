<?php

use LaravelBook\Ardent\Ardent as Ardent;

/**
 * PiggybankEvent
 *
 * @property integer         $id
 * @property \Carbon\Carbon  $created_at
 * @property \Carbon\Carbon  $updated_at
 * @property integer         $piggybank_id
 * @property \Carbon\Carbon  $date
 * @property float           $amount
 * @property-read \Piggybank $piggybank
 * @method static \Illuminate\Database\Query\Builder|\PiggybankEvent whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankEvent wherePiggybankId($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankEvent whereDate($value)
 * @method static \Illuminate\Database\Query\Builder|\PiggybankEvent whereAmount($value)
 */
class PiggybankEvent extends Ardent
{

    public static $rules
        = ['piggybank_id' => 'required|exists:piggybanks,id', 'date' => 'required|date', 'amount' => 'required|numeric'];

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }

} 