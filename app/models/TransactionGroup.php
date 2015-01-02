<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Watson\Validating\ValidatingTrait;
use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class TransactionGroup
 */
class TransactionGroup extends Eloquent
{
    use SoftDeletingTrait, ValidatingTrait;

    public static $rules
        = [
            'relation' => 'required|in:balance'
        ];

    /**
     * @return array
     */
    public function getDates()
    {
        return ['created_at', 'updated_at'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionjournals()
    {
        return $this->belongsToMany('TransactionJournal');
    }

    /**
     * User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User');
    }


} 
