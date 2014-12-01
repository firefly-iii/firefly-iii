<?php
use LaravelBook\Ardent\Ardent;

/**
 * TransactionGroup
 *
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $user_id
 * @property string $relation
 * @property-read \Illuminate\Database\Eloquent\Collection|\TransactionJournal[] $transactionjournals
 * @property-read \User $user
 * @method static \Illuminate\Database\Query\Builder|\TransactionGroup whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionGroup whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionGroup whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionGroup whereUserId($value) 
 * @method static \Illuminate\Database\Query\Builder|\TransactionGroup whereRelation($value) 
 */
class TransactionGroup extends Ardent
{

    public static $rules = [
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