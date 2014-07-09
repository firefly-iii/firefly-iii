<?php


class TransactionType extends Eloquent {
    public function transactionJournals() {
        return $this->hasMany('TransactionJournal');
    }

    public static $factory = [
        'type' => 'string'
    ];

} 