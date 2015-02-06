<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{

    public function account()
    {
        return $this->belongsTo('Account');
    }

    public function transactionJournal()
    {
        return $this->belongsTo('TransactionJournal');
    }

}
