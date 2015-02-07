<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{


    public function budgetlimits()
    {
        return $this->hasMany('FireflyIII\Models\BudgetLimit');
    }

    public function limitrepetitions()
    {
        return $this->hasManyThrough('FireflyIII\Models\LimitRepetition', 'BudgetLimit', 'budget_id');
    }

    public function transactionjournals()
    {
        return $this->belongsToMany('FireflyIII\Models\TransactionJournal', 'budget_transaction_journal', 'budget_id');
    }

    public function user()
    {
        return $this->belongsTo('FireflyIII\User');
    }
    public function getDates()
    {
        return ['created_at', 'updated_at','deleted_at'];
    }


}
