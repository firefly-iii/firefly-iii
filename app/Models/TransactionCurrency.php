<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionCurrency extends Model
{

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
