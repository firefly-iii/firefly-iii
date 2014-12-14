<?php
use Watson\Validating\ValidatingTrait;

/**
 * Class PiggyBankEvent
 */
class PiggyBankEvent extends Eloquent
{

    public static $rules
        = [
            'piggybank_id' => 'required|exists:piggybanks,id',
            'date'         => 'required|date',
            'amount'       => 'required|numeric'
        ];
    use ValidatingTrait;

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