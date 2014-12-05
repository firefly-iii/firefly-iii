<?php
use LaravelBook\Ardent\Ardent as Ardent;


/**
 * AccountMeta
 *
 * @property-read \Account $account
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $account_id
 * @property string $name
 * @property string $data
 * @method static \Illuminate\Database\Query\Builder|\AccountMeta whereId($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountMeta whereCreatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountMeta whereUpdatedAt($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountMeta whereAccountId($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountMeta whereName($value) 
 * @method static \Illuminate\Database\Query\Builder|\AccountMeta whereData($value) 
 */
class AccountMeta extends Ardent
{
    /**
     * @var array
     */
    public static $rules
        = [
            'account_id' => 'numeric|required|exists:accounts,id',
            'name'       => 'required|between:1,250',
            'data'       => 'required'
        ];
    /**
     * @var array
     */
    protected $fillable = ['account_id', 'name', 'date'];
    protected $table    = 'account_meta';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo('Account');
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * @param $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

} 