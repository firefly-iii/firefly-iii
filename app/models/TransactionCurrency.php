<?php


class TransactionCurrency extends Eloquent
{

    public static $factory
        = [
            'code' => 'string'
        ];

    public function transactionJournals()
    {
        return $this->hasMany('TransactionJournal');
    }

} 