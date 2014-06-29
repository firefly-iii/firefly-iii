<?php


class Transaction extends Eloquent
{

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
} 