<?php
use LaravelBook\Ardent\Ardent;

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