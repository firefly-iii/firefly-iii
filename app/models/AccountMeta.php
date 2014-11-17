<?php
use LaravelBook\Ardent\Ardent as Ardent;


/**
 * AccountMeta
 *
 * @property-read \Account $account
 */
class AccountMeta extends Ardent
{
    /**
     * @var array
     */
    public static $rules
        = ['account_id' => 'numeric|required|exists:accounts,id', 'name' => 'required|between:1,250', 'data' => 'required'];

    /**
     * @var array
     */
    protected $fillable = ['account_id', 'name', 'date'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
    }

} 