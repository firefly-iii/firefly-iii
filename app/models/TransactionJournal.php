<?php


class TransactionJournal extends Eloquent {

    public function transactionType() {
        return $this->belongsTo('TransactionType');
    }
    public function transactionCurrency() {
        return $this->belongsTo('TransactionCurrency');
    }

    public function transactions() {
        return $this->hasMany('Transaction');
    }

} 