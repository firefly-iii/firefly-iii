<?php


class TransactionJournal extends Elegant
{

    public static $rules
        = [
            'transaction_type_id'     => 'required|exists:transaction_types,id',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'description'             => 'between:1,255',
            'date'                    => 'date',
        ];

    public function transactionType()
    {
        return $this->belongsTo('TransactionType');
    }

    public function transactionCurrency()
    {
        return $this->belongsTo('TransactionCurrency');
    }

    public function transactions()
    {
        return $this->hasMany('Transaction');
    }

    public function getDates()
    {
        return array('created_at', 'updated_at', 'date');
    }

} 