<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{

    public function transactionjournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }


}
