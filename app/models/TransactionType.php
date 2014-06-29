<?php


class TransactionType extends Eloquent {
    public function transactionJournals() {
        return $this->hasMany('TransactionJournal');
    }

} 