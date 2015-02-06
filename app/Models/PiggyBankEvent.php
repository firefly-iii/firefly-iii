<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class PiggyBankEvent extends Model
{

    public function piggyBank()
    {
        return $this->belongsTo('PiggyBank');
    }

    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }

}
