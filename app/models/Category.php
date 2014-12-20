<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;

/**
 * Class Category
 */
class Category extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;
    protected $fillable = ['name', 'user_id'];
    protected $rules
                        = [
            'user_id' => 'exists:users,id|required',
            'name'    => 'required|between:1,100|alphabasic',
        ];

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