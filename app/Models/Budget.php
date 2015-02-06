<?php namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{


    public function budgetlimits()
    {
        return $this->hasMany('BudgetLimit');
    }

    public function limitrepetitions()
    {
        return $this->hasManyThrough('LimitRepetition', 'BudgetLimit', 'budget_id');
    }

    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal', 'budget_transaction_journal', 'budget_id');
    }

    public function user()
    {
        return $this->belongsTo('User');
    }


}
