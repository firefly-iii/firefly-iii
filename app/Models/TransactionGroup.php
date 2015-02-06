<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionGroup extends Model
{

    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }


}
