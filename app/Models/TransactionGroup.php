<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionGroup extends Model
{

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
