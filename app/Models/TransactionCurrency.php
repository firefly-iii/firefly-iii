<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionCurrency extends Model
{
    use SoftDeletes;

    public function getDates()
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
