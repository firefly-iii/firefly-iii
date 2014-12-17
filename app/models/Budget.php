<?php

use Illuminate\Database\Eloquent\SoftDeletingTrait;

/**
 * Class Budget
 */
class Budget extends Eloquent
{
    use SoftDeletingTrait;
    protected     $fillable      = ['name', 'user_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function limitrepetitions()
    {
        return $this->hasManyThrough('LimitRepetition', 'BudgetLimit', 'budget_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function budgetlimits()
    {
        return $this->hasMany('BudgetLimit', 'budget_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal', 'budget_transaction_journal', 'budget_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }

} 