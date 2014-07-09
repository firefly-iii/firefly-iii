<?php


class Transaction extends Elegant
{
    public static $rules
        = [
            'account_id'             => 'numeric|required|exists:accounts,id',
            'transaction_journal_id' => 'numeric|required|exists:transaction_journals,id',
            'description'            => 'between:1,255',
            'amount'                 => 'required|between:-65536,65536',
        ];

    public static $factory
        = [
            'account_id'             => 'factory|Account',
            'transaction_journal_id' => 'factory|TransactionJournal',
            'description'            => 'string',
            'amount'                 => 'integer:5'
        ];

    public function account()
    {
        return $this->belongsTo('Account');
    }

    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }

    public function components()
    {
        return $this->belongsToMany('Component');
    }

    public function budgets()
    {
        return $this->belongsToMany('Budget');
    }

    public function categories()
    {
        return $this->belongsToMany('Category');
    }
} 