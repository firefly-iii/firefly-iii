<?php


class TransactionJournal extends Elegant
{

    public static $rules
        = [
            'transaction_type_id'     => 'required|exists:transaction_types,id',
            'transaction_currency_id' => 'required|exists:transaction_currencies,id',
            'description'             => 'between:1,255',
            'date'                    => 'date',
            'completed'               => 'required|between:0,1'
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

    public function components()
    {
        return $this->belongsToMany('Component');
    }

    public function budgets()
    {
        return $this->belongsToMany('Budget','component_transaction_journal','transaction_journal_id','component_id');
    }

    public function categories()
    {
        return $this->belongsToMany('Category','component_transaction_journal','transaction_journal_id','component_id');
    }

    public function getDates()
    {
        return array('created_at', 'updated_at', 'date');
    }

} 