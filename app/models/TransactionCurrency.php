<?php


class TransactionCurrency extends Eloquent {

    public function transactionJournals() {
        return $this->hasMany('TransactionJournal');
    }

} 