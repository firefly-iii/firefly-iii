<?php
use Watson\Validating\ValidatingTrait;

/**
 * Class PiggybankEvent
 */
class PiggybankEvent extends Eloquent
{

    use ValidatingTrait;
    public static $rules
        = [
            'piggybank_id' => 'required|exists:piggybanks,id',
            'date'         => 'required|date',
            'amount'       => 'required|numeric'
        ];

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