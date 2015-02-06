<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'category_transaction_journal', 'category_id');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }

}
