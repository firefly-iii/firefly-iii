<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;
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

    /**
     *  remove this method in favour of something in the FireflyIII libraries.
     *
     * @return Carbon
     */
    public function lastActionDate()
    {
        $transaction = $this->transactionjournals()->orderBy('updated_at', 'DESC')->first();
        if (is_null($transaction)) {
            return null;
        }

        return $transaction->date;
    }
}