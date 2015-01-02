<?php
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
/**
 * Class PiggyBankEvent
 */
class PiggyBankEvent extends Eloquent
{

    public static $rules
        = [
            'piggy_bank_id' => 'required|exists:piggy_banks,id',
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
    public function piggyBank()
    {
        return $this->belongsTo('PiggyBank');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }

} 
