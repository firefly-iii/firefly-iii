<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;

/**
 * Class Category
 */
class Category extends Eloquent
{
    use SoftDeletingTrait;
    protected     $fillable      = ['name', 'user_id'];

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