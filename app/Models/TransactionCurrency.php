<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCurrency extends Model
{

    public function transactionJournals()
    {
        return $this->hasMany('TransactionJournal');
    }

}
